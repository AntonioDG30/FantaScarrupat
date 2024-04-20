DROP SCHEMA IF EXISTS my_fantascarrupat;
CREATE SCHEMA my_fantascarrupat;
USE my_fantascarrupat;

CREATE TABLE giocatore (
  id_giocatore INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
  codice_fantacalcio INT NOT NULL,
  nome VARCHAR(255) NOT NULL,
  ruolo VARCHAR(255) NOT NULL,
  squadra_reale VARCHAR(255) NOT NULL
);

CREATE TABLE fantasquadra (
  nome_fantasquadra VARCHAR(255) PRIMARY KEY NOT NULL,
  scudetto VARCHAR(255),
  fantallenature VARCHAR(255) NOT NULL
);

CREATE TABLE rosa (
  id_rosa INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
  nome_fantasquadra VARCHAR(255) NOT NULL,
  id_giocatore INT NOT NULL,
  anno INT NOT NULL,
  FOREIGN KEY (nome_fantasquadra) REFERENCES fantasquadra(nome_fantasquadra),
  FOREIGN KEY (id_giocatore) REFERENCES giocatore(id_giocatore)
);

CREATE TABLE tipologia_competizione (
  tipologia VARCHAR(100) PRIMARY KEY NOT NULL
);

CREATE TABLE competizione (
  nome_competizione VARCHAR(255) PRIMARY KEY NOT NULL,
  tipologia VARCHAR(255) NOT NULL,
  FOREIGN KEY (tipologia) REFERENCES tipologia_competizione(tipologia)
);

CREATE TABLE competizione_disputata (
  id_competizione_disputata INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
  nome_competizione VARCHAR(255) NOT NULL,
  anno INT NOT NULL,
  vincitore VARCHAR(255) NOT NULL,
  FOREIGN KEY (nome_competizione) REFERENCES competizione(nome_competizione),
  FOREIGN KEY (vincitore) REFERENCES fantasquadra(nome_fantasquadra)
);

CREATE TABLE tipologia_partita (
  tipologia VARCHAR(100) PRIMARY KEY
);

CREATE TABLE partita_avvessario (
  id_partita INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
  id_competizione_disputata INT NOT NULL,
  nome_fantasquadra_casa VARCHAR(255) NOT NULL,
  nome_fantasquadra_trasferta VARCHAR(255) NOT NULL,
  gol_casa INT NOT NULL,
  gol_trasferta INT NOT NULL,
  punti_casa FLOAT NOT NULL,
  punti_trasferta FLOAT NOT NULL,
  giornata INT NOT NULL,
  tipologia VARCHAR(255) NOT NULL,
  girone VARCHAR(255) NOT NULL,
  FOREIGN KEY (tipologia) REFERENCES tipologia_partita(tipologia),
  FOREIGN KEY (nome_fantasquadra_casa) REFERENCES fantasquadra(nome_fantasquadra),
  FOREIGN KEY (nome_fantasquadra_trasferta) REFERENCES fantasquadra(nome_fantasquadra),
  FOREIGN KEY (id_competizione_disputata) REFERENCES competizione_disputata(id_competizione_disputata)
);

CREATE TABLE partita_solitaria (
  id_partita_solitaria INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
  id_competizione_disputata INT NOT NULL,
  nome_fantasquadra VARCHAR(255) NOT NULL,
  punteggio FLOAT NOT NULL,
  giornata INT NOT NULL,
  FOREIGN KEY (nome_fantasquadra) REFERENCES fantasquadra(nome_fantasquadra),
  FOREIGN KEY (id_competizione_disputata) REFERENCES competizione_disputata(id_competizione_disputata)
);

CREATE TABLE classifica (
  id_competizione_disputata INT NOT NULL,
  nome_fantasquadra VARCHAR(255) NOT NULL,
  punteggio_totale INT,
  punti INT,
  FOREIGN KEY (id_competizione_disputata) REFERENCES competizione_disputata(id_competizione_disputata),
  FOREIGN KEY (nome_fantasquadra) REFERENCES fantasquadra(nome_fantasquadra),
  PRIMARY KEY (id_competizione_disputata, nome_fantasquadra)
);

CREATE TABLE informazioni_extra_classifica (
  id_competizione_disputata INT NOT NULL,
  nome_fantasquadra VARCHAR(255) NOT NULL,
  gol_fatti INT NOT NULL,
  gol_subiti INT NOT NULL,
  numero_vittorie INT NOT NULL,
  numero_sconfitte INT NOT NULL,
  numero_pareggi INT NOT NULL,
  FOREIGN KEY (id_competizione_disputata) REFERENCES competizione_disputata(id_competizione_disputata),
  FOREIGN KEY (nome_fantasquadra) REFERENCES fantasquadra(nome_fantasquadra),
  PRIMARY KEY (id_competizione_disputata, nome_fantasquadra)
);
