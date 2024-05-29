-- Creazione del database FantaScarrupat
DROP SCHEMA IF EXISTS my_fantascarrupat;
CREATE SCHEMA my_fantascarrupat;
USE my_fantascarrupat;

-- Creazione delle entità
CREATE TABLE giocatore (
                         id_giocatore INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                         codice_fantacalcio INT NOT NULL,
                         nome_giocatore VARCHAR(100) NOT NULL,
                         ruolo VARCHAR(100) NOT NULL,
                         squadra_reale VARCHAR(100) NOT NULL
);

CREATE TABLE fantasquadra (
                            nome_fantasquadra VARCHAR(100) PRIMARY KEY NOT NULL,
                            scudetto VARCHAR(100),
                            fantallenatore VARCHAR(100) NOT NULL,
                            immagine_fantallenatore VARCHAR(100) NOT NULL,
                            flag_attuale VARCHAR(1) NOT NULL -- 1 = FANTASQUADRA ATTUALE, 0 = FANTASQUADRA PASSATA
);

CREATE TABLE rosa (
                    id_rosa INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                    nome_fantasquadra VARCHAR(100) NOT NULL,
                    id_giocatore INT NOT NULL,
                    crediti_pagati INT NOT NULL,
                    anno INT NOT NULL,
                    FOREIGN KEY (nome_fantasquadra) REFERENCES fantasquadra(nome_fantasquadra),
                    FOREIGN KEY (id_giocatore) REFERENCES giocatore(id_giocatore)
);

CREATE TABLE tipologia_competizione (
                                      tipologia VARCHAR(100) PRIMARY KEY NOT NULL
);

CREATE TABLE competizione (
                            nome_competizione VARCHAR(100) PRIMARY KEY NOT NULL,
                            tipologia VARCHAR(100) NOT NULL,
                            FOREIGN KEY (tipologia) REFERENCES tipologia_competizione(tipologia)
);

CREATE TABLE competizione_disputata (
                                      id_competizione_disputata INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                                      nome_competizione VARCHAR(100) NOT NULL,
                                      anno INT NOT NULL,
                                      vincitore VARCHAR(100),
                                      FOREIGN KEY (nome_competizione) REFERENCES competizione(nome_competizione),
                                      FOREIGN KEY (vincitore) REFERENCES fantasquadra(nome_fantasquadra)
);

CREATE TABLE tipologia_partita (
                                 tipologia VARCHAR(100) PRIMARY KEY
);

CREATE TABLE partita_avvessario (
                                  id_partita INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                                  id_competizione_disputata INT NOT NULL,
                                  nome_fantasquadra_casa VARCHAR(100) NOT NULL,
                                  nome_fantasquadra_trasferta VARCHAR(100) NOT NULL,
                                  gol_casa INT NOT NULL,
                                  gol_trasferta INT NOT NULL,
                                  punteggio_casa FLOAT NOT NULL,
                                  punteggio_trasferta FLOAT NOT NULL,
                                  giornata INT NOT NULL,
                                  tipologia VARCHAR(100) NOT NULL,
                                  girone VARCHAR(100),
                                  FOREIGN KEY (tipologia) REFERENCES tipologia_partita(tipologia),
                                  FOREIGN KEY (nome_fantasquadra_casa) REFERENCES fantasquadra(nome_fantasquadra),
                                  FOREIGN KEY (nome_fantasquadra_trasferta) REFERENCES fantasquadra(nome_fantasquadra),
                                  FOREIGN KEY (id_competizione_disputata) REFERENCES competizione_disputata(id_competizione_disputata)
);

CREATE TABLE partita_solitaria (
                                 id_partita_solitaria INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                                 id_competizione_disputata INT NOT NULL,
                                 nome_fantasquadra VARCHAR(100) NOT NULL,
                                 punteggio FLOAT NOT NULL,
                                 giornata INT NOT NULL,
                                 FOREIGN KEY (nome_fantasquadra) REFERENCES fantasquadra(nome_fantasquadra),
                                 FOREIGN KEY (id_competizione_disputata) REFERENCES competizione_disputata(id_competizione_disputata)
);

CREATE TABLE immagine (
                        id_immagine INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
                        nome_immagine VARCHAR(100) NOT NULL,
                        descrizione_immagine VARCHAR(100) NOT NULL,
                        flag_visibile VARCHAR(1) NOT NULL -- 1 = IMMAGINE DA VISUALIZZARE, 0 = IMMAGINE DA NON VISUALIZZARE
);

CREATE TABLE sessions (
                        id_sessions INT AUTO_INCREMENT PRIMARY KEY,
                        ip_address VARCHAR(100),
                        start_time DATETIME,
                        last_activity DATETIME
);

CREATE TABLE page_views (
                          id_page_views INT AUTO_INCREMENT PRIMARY KEY,
                          date DATE,
                          page_url VARCHAR(100),
                          views INT DEFAULT 0
);

CREATE TABLE admin (
                     email VARCHAR(100) PRIMARY KEY,
                     password VARCHAR(100),
                     nome VARCHAR(100),
                     cognome VARCHAR(100),
                     data_nascita date
);

-- INSERIMENTO DATI
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('AntFeud', 'AntFeud.png', 'Antonio Di Giorgio', 'team-2.jpg', '1');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('FC Pocholoco', 'Pocholoco.jpg', 'Pasquale Lupoli', 'team-2.jpg', '1');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('FC Lukand', 'FCLukand.jpeg', 'Andrea Lucariello', 'team-2.jpg', '1');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('Fc Ludopatici', 'FCLudopatici.jpeg', 'Lorenzo Parolisi', 'team-2.jpg', '1');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('Lambrate FC', 'LambrateFC.jpeg', 'Cristian Cecere', 'team-2.jpg', '1');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('Napolethanos', 'Napolethanos.jpeg', 'Mario Castaldi', 'team-2.jpg', '1');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('FC Volante', 'FCVolante.png', 'Vincenzo Gervasio', 'team-2.jpg', '1');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('Ingegneri del Goal', 'IngegneriDelGoal.png', 'Francesco Parolisi', 'team-2.jpg', '1');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('Quadrato Team', 'QuadratoTeam.png', 'Giuseppe Costanzo', 'team-2.jpg', '0');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('Mainz Na Gioia', 'MainzNaGioia.jpeg', 'Emanuele Torcia', 'team-2.jpg', '0');
INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
VALUES ('FC Alastor', 'FCAlastor.jpeg', 'Antonio Guarino', 'team-2.jpg', '0');

INSERT INTO tipologia_competizione (tipologia)
VALUES ('A Calendario');
INSERT INTO tipologia_competizione (tipologia)
VALUES ('Eliminazione Diretta');
INSERT INTO tipologia_competizione (tipologia)
VALUES ('A Gruppi');
INSERT INTO tipologia_competizione (tipologia)
VALUES ('Uno vs Tutti');
INSERT INTO tipologia_competizione (tipologia)
VALUES ('Battle Royale');
INSERT INTO tipologia_competizione (tipologia)
VALUES ('Formula Uno');
INSERT INTO tipologia_competizione (tipologia)
VALUES ('Highlander');

INSERT INTO competizione (nome_competizione, tipologia)
VALUES ('Serie A', 'A Calendario');
INSERT INTO competizione (nome_competizione, tipologia)
VALUES ('Champions League', 'A Gruppi');
INSERT INTO competizione (nome_competizione, tipologia)
VALUES ('Coppa Italia', 'Eliminazione Diretta');
INSERT INTO competizione (nome_competizione, tipologia)
VALUES ('Battle Royale', 'Battle Royale');
INSERT INTO competizione (nome_competizione, tipologia)
VALUES ('Formula 1', 'Formula Uno');

INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Serie A', '2017', 'Quadrato Team');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Serie A', '2018', 'Napolethanos');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Serie A', '2019', 'AntFeud');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Serie A', '2020', 'AntFeud');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Serie A', '2021', 'Mainz Na Gioia');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Serie A', '2022', 'FC Lukand');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Serie A', '2023', 'FC Volante');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Champions League', '2019', 'FC Pocholoco');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Champions League', '2020', 'FC Pocholoco');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Champions League', '2021', 'FC Volante');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Champions League', '2022', 'FC Lukand');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Champions League', '2023', 'Napolethanos');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Coppa Italia', '2021', 'FC Alastor');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Battle Royale', '2022', 'Lambrate FC');
INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
VALUES ('Formula 1', '2023', 'Napolethanos');

INSERT INTO tipologia_partita (tipologia)
VALUES ('Calendario');
INSERT INTO tipologia_partita (tipologia)
VALUES ('Fase a Gironi');
INSERT INTO tipologia_partita (tipologia)
VALUES ('Ottavi di Finale');
INSERT INTO tipologia_partita (tipologia)
VALUES ('Quarti di Finale');
INSERT INTO tipologia_partita (tipologia)
VALUES ('Semifinali');
INSERT INTO tipologia_partita (tipologia)
VALUES ('Finale');

INSERT INTO immagine (nome_immagine, descrizione_immagine, flag_visibile)
VALUES ('Asta_2019.jpg', 'Asta 2019', '1');
INSERT INTO immagine (nome_immagine, descrizione_immagine, flag_visibile)
VALUES ('Asta_2023.jpg', 'Asta 2019', '1');
