-- Seed: Business line
INSERT INTO business_lines (nome, ordine, attiva) VALUES
  ('Business', 10, 1),
  ('Enterprise', 20, 1),
  ('Online', 30, 1),
  ('Business Line A', 40, 1),
  ('Corporate', 50, 1),
  ('Enterprise / Online', 60, 1),
  ('Business Line B', 70, 1),
  ('Altre linee', 80, 1)
ON DUPLICATE KEY UPDATE ordine = VALUES(ordine), attiva = VALUES(attiva);

