from __future__ import annotations

import argparse
import json
import re
from email import policy
from email.parser import BytesParser
from pathlib import Path

from lxml import html


HEADER_MAP = {
    "ID REQUISITO": "codice",
    "VERSIONE": "versione",
    "NOME REQUISITO": "titolo",
    "DESCRIZIONE REQUISITO": "descrizione",
    "CONTESTO DI APPLICABILITÀ": "contesto",
    "NOTE": "note",
    "IMPORTANZA": "importanza",
    "FASE": "fase",
    "OWNER": "owner",
    "FUNZIONALE / TECNOLOGICO": "funzionale_tecnologico",
    "DATA PROTECTION": "data_protection",
    "RIF. ISO": "rif_iso",
    "RIF.FNCS": "rif_fncs",
    "Software selection": "software_selection",
    "Riferimento Standardizzazione Progettazione DC (HLD)": "riferimento_hld",
    "Pubblicato su LGA": "pubblicato_lga",
    "RIF. STD config DC": "rif_std_config_dc",
    "Standardizzazione Controllo (Task)": "standardizzazione_controllo_task",
    "RIF. Procedura di Controllo / Collaudo": "rif_procedura_controllo",
    "ULTIMO UPDATE": "ultimo_update",
}


def clean_text(value: str) -> str:
    return " ".join(value.replace("\xa0", " ").split())


def table_rows(table) -> list[list[str]]:
    rows = []
    for tr in table.xpath(".//tr"):
        cells = [clean_text(" ".join(cell.itertext())) for cell in tr.xpath("./th|./td")]
        rows.append(cells)
    return rows


def acronym_from_code(code: str) -> str:
    match = re.match(r"^SEC-([A-Z]+)", code)
    return match.group(1) if match else ""


def image_extension(data: bytes, fallback: str = "bin") -> str:
    if data.startswith(b"\x89PNG\r\n\x1a\n"):
        return "png"
    if data.startswith(b"\xff\xd8\xff"):
        return "jpg"
    if data.startswith(b"GIF87a") or data.startswith(b"GIF89a"):
        return "gif"
    return fallback


def main() -> None:
    parser = argparse.ArgumentParser(description="Estrae il catalogo requisiti da export DOC/MHTML Confluence.")
    parser.add_argument("doc", type=Path)
    parser.add_argument("--json-out", type=Path, default=Path("webapp/database/catalogo_requisiti_doc.json"))
    parser.add_argument("--asset-dir", type=Path, default=Path("webapp/public/catalogo_assets"))
    args = parser.parse_args()

    message = BytesParser(policy=policy.default).parsebytes(args.doc.read_bytes())
    html_text = ""
    attachments: list[dict[str, str]] = []
    args.asset_dir.mkdir(parents=True, exist_ok=True)

    for part in message.walk():
        if part.get_content_type() == "text/html":
            html_text = part.get_payload(decode=True).decode(part.get_content_charset() or "utf-8", errors="replace")
            continue

        payload = part.get_payload(decode=True)
        if not payload or part.is_multipart():
            continue

        filename = ""
        content_location = part.get("Content-Location") or ""
        if content_location:
            filename = Path(content_location.replace("file:///C:/", "")).name
        ext = image_extension(payload)
        if not filename or "." not in filename:
            filename = f"catalogo_allegato_{len(attachments) + 1}.{ext}"
        target = args.asset_dir / filename
        target.write_bytes(payload)
        attachments.append(
            {
                "filename": filename,
                "mime_type": part.get_content_type(),
                "path": "public/catalogo_assets/" + filename,
                "content_location": content_location,
            }
        )

    if not html_text:
        raise RuntimeError("Nessuna parte HTML trovata nel file DOC/MHTML.")

    root = html.fromstring(html_text)
    tables = root.xpath("//table")
    if len(tables) < 4:
        raise RuntimeError("Tabella requisiti non trovata nel documento.")

    acronym_categories: dict[str, dict[str, str]] = {}
    for row in table_rows(tables[2])[1:]:
        if len(row) >= 3 and row[0]:
            acronym_categories[row[0]] = {"function": row[1], "category": row[2]}

    requirement_rows = table_rows(tables[3])
    headers = requirement_rows[0]
    requirements = []
    skipped_rows = []
    for index, row in enumerate(requirement_rows[1:], start=2):
        if len(row) != len(headers) or not row or not re.match(r"^SEC-[A-Z0-9]+-\d+(?:\.\d+)?$", row[0]):
            skipped_rows.append({"row": index, "columns": len(row), "preview": row[:3]})
            continue
        item = {HEADER_MAP.get(header, header): row[pos] for pos, header in enumerate(headers)}
        category = acronym_categories.get(acronym_from_code(item["codice"]), {})
        item["categoria"] = category.get("category", "")
        item["sottocategoria"] = item.get("rif_fncs", "")
        item["framework_function"] = category.get("function", "")
        item["catalogo_source"] = args.doc.name
        requirements.append(item)

    args.json_out.parent.mkdir(parents=True, exist_ok=True)
    args.json_out.write_text(
        json.dumps(
            {
                "source_file": args.doc.name,
                "requirements": requirements,
                "attachments": attachments,
                "skipped_rows": skipped_rows,
            },
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )
    print(f"Requisiti estratti: {len(requirements)}")
    print(f"Allegati estratti: {len(attachments)}")
    print(f"Righe ignorate: {len(skipped_rows)}")
    print(args.json_out)


if __name__ == "__main__":
    main()
