-- INSTALL.sql
CREATE DATABASE IF NOT EXISTS requisiti CHARACTER SET utf8mb4;
USE requisiti;
SOURCE schema.sql;
SOURCE seed_domande.sql;
SOURCE seed_requisiti.sql;
SOURCE seed_servizi.sql;
SOURCE seed_regole.sql;