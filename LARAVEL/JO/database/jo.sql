-- Base de données SQLite du site Jeux Olympiques (Laravel), générée par php artisan jo:export-sql
-- Données de démonstration : pays réels, athlètes et résultats fictifs.
-- Comptes : admin@jo.test / password (admin), spectateur@jo.test / password
-- Import : sqlite3 database/database.sqlite < database/jo.sql

PRAGMA foreign_keys = OFF;
BEGIN TRANSACTION;

DROP TABLE IF EXISTS "tickets";
DROP TABLE IF EXISTS "results";
DROP TABLE IF EXISTS "athletes";
DROP TABLE IF EXISTS "events";
DROP TABLE IF EXISTS "venues";
DROP TABLE IF EXISTS "sports";
DROP TABLE IF EXISTS "countries";
DROP TABLE IF EXISTS "password_reset_tokens";
DROP TABLE IF EXISTS "users";
DROP TABLE IF EXISTS "migrations";

-- ---------------------------------------------------------------- Structure

CREATE TABLE "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);
CREATE TABLE "users" ("id" integer primary key autoincrement not null, "name" varchar not null, "email" varchar not null, "password" varchar not null, "role" varchar not null default 'spectator', "remember_token" varchar, "created_at" datetime, "updated_at" datetime);
CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");
CREATE TABLE "password_reset_tokens" ("email" varchar not null, "token" varchar not null, "created_at" datetime, primary key ("email"));
CREATE TABLE "countries" ("id" integer primary key autoincrement not null, "name" varchar not null, "code" varchar not null, "iso" varchar not null);
CREATE UNIQUE INDEX "countries_code_unique" on "countries" ("code");
CREATE TABLE "sports" ("id" integer primary key autoincrement not null, "name" varchar not null, "icon" varchar not null, "description" text, "two_bronzes" tinyint(1) not null default '0');
CREATE UNIQUE INDEX "sports_name_unique" on "sports" ("name");
CREATE TABLE "venues" ("id" integer primary key autoincrement not null, "name" varchar not null, "city" varchar not null, "capacity" integer not null);
CREATE TABLE "events" ("id" integer primary key autoincrement not null, "sport_id" integer not null, "venue_id" integer not null, "name" varchar not null, "gender" varchar not null, "team" tinyint(1) not null default '0', "starts_at" datetime not null, "price" integer not null, "capacity" integer not null, "cancelled_at" datetime, "created_at" datetime, "updated_at" datetime, foreign key("sport_id") references "sports"("id") on delete cascade, foreign key("venue_id") references "venues"("id") on delete restrict);
CREATE TABLE "athletes" ("id" integer primary key autoincrement not null, "first_name" varchar not null, "last_name" varchar not null, "gender" varchar not null, "country_id" integer not null, "sport_id" integer not null, foreign key("country_id") references "countries"("id") on delete cascade, foreign key("sport_id") references "sports"("id") on delete cascade);
CREATE TABLE "results" ("id" integer primary key autoincrement not null, "event_id" integer not null, "country_id" integer not null, "athlete_id" integer, "medal" varchar not null, foreign key("event_id") references "events"("id") on delete cascade, foreign key("country_id") references "countries"("id") on delete cascade, foreign key("athlete_id") references "athletes"("id") on delete cascade);
CREATE UNIQUE INDEX "results_event_id_athlete_id_unique" on "results" ("event_id", "athlete_id");
CREATE TABLE "tickets" ("id" integer primary key autoincrement not null, "user_id" integer, "event_id" integer not null, "quantity" integer not null, "unit_price" integer not null, "cancelled_at" datetime, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete set null, foreign key("event_id") references "events"("id") on delete cascade);

-- ---------------------------------------------------------------- Données

-- migrations (8 lignes)
INSERT INTO "migrations" ("id", "migration", "batch") VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '2026_09_30_000001_create_countries_table', 1),
(3, '2026_09_30_000002_create_sports_table', 1),
(4, '2026_09_30_000003_create_venues_table', 1),
(5, '2026_09_30_000004_create_events_table', 1),
(6, '2026_09_30_000005_create_athletes_table', 1),
(7, '2026_09_30_000006_create_results_table', 1),
(8, '2026_09_30_000007_create_tickets_table', 1);

-- users (2 lignes)
INSERT INTO "users" ("id", "name", "email", "password", "role", "remember_token", "created_at", "updated_at") VALUES
(1, 'Administrateur', 'admin@jo.test', '$2y$12$3gvlqcRkX3Z/jgwieZ6FluAF7LiqMyjueHtAHNqSN9/1sK3NrzxN6', 'admin', 'vxrXt0WNxu', '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(2, 'Camille Spectatrice', 'spectateur@jo.test', '$2y$12$3gvlqcRkX3Z/jgwieZ6FluAF7LiqMyjueHtAHNqSN9/1sK3NrzxN6', 'spectator', 'TM7UqscQHl', '2026-09-30 13:44:29', '2026-09-30 13:44:29');

-- countries (15 lignes)
INSERT INTO "countries" ("id", "name", "code", "iso") VALUES
(1, 'France', 'FRA', 'fr'),
(2, 'États-Unis', 'USA', 'us'),
(3, 'Chine', 'CHN', 'cn'),
(4, 'Japon', 'JPN', 'jp'),
(5, 'Grande-Bretagne', 'GBR', 'gb'),
(6, 'Australie', 'AUS', 'au'),
(7, 'Italie', 'ITA', 'it'),
(8, 'Allemagne', 'GER', 'de'),
(9, 'Pays-Bas', 'NED', 'nl'),
(10, 'Brésil', 'BRA', 'br'),
(11, 'Espagne', 'ESP', 'es'),
(12, 'Canada', 'CAN', 'ca'),
(13, 'Corée du Sud', 'KOR', 'kr'),
(14, 'Suède', 'SWE', 'se'),
(15, 'Norvège', 'NOR', 'no');

-- sports (12 lignes)
INSERT INTO "sports" ("id", "name", "icon", "description", "two_bronzes") VALUES
(1, 'Athlétisme', '🏃', 'Courses, sauts et lancers : le sport roi des Jeux.', 0),
(2, 'Natation', '🏊', 'Des bassins où se jouent les records au centième près.', 0),
(3, 'Judo', '🥋', 'Art martial japonais, discipline phare de la délégation française.', 1),
(4, 'Cyclisme sur piste', '🚴', 'Vitesse et tactique sur l''anneau du vélodrome.', 0),
(5, 'Escrime', '🤺', 'Fleuret, épée et sabre : l''élégance du duel.', 0),
(6, 'Basketball', '🏀', 'Tournois masculin et féminin jusqu''aux finales.', 0),
(7, 'Gymnastique', '🤸', 'Force, souplesse et précision aux agrès.', 0),
(8, 'Tennis', '🎾', 'Le tournoi olympique sur terre battue.', 0),
(9, 'Aviron', '🚣', 'Endurance et synchronisation sur 2 000 mètres.', 0),
(10, 'Tir à l''arc', '🏹', 'Concentration absolue à 70 mètres de la cible.', 0),
(11, 'Boxe', '🥊', 'Le noble art sur le ring olympique.', 1),
(12, 'Surf', '🏄', 'Les meilleures vagues du monde pour les surfeurs olympiques.', 0);

-- venues (9 lignes)
INSERT INTO "venues" ("id", "name", "city", "capacity") VALUES
(1, 'Stade Olympique', 'Saint-Denis', 77000),
(2, 'Centre Aquatique Olympique', 'Saint-Denis', 5000),
(3, 'Arena Olympique', 'Paris', 15000),
(4, 'Vélodrome National', 'Saint-Quentin-en-Yvelines', 5000),
(5, 'Palais des Sports', 'Paris', 8000),
(6, 'Stade Nautique', 'Vaires-sur-Marne', 14000),
(7, 'Court Central', 'Paris', 15000),
(8, 'Esplanade des Invalides', 'Paris', 8000),
(9, 'Spot de Teahupo''o', 'Tahiti', 1000);

-- events (28 lignes)
INSERT INTO "events" ("id", "sport_id", "venue_id", "name", "gender", "team", "starts_at", "price", "capacity", "cancelled_at", "created_at", "updated_at") VALUES
(1, 1, 1, '100 m', 'H', 0, '2026-09-22 15:00:00', 50, 77000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(2, 1, 1, '100 m', 'F', 0, '2026-09-23 13:00:00', 180, 77000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(3, 1, 1, 'Marathon', 'F', 0, '2026-09-24 14:00:00', 50, 77000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(4, 1, 1, 'Saut en longueur', 'H', 0, '2026-09-25 10:30:00', 120, 77000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(5, 2, 2, '100 m nage libre', 'F', 0, '2026-09-26 21:00:00', 120, 5000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(6, 2, 2, '200 m papillon', 'H', 0, '2026-09-27 16:00:00', 180, 5000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(7, 2, 2, 'Relais 4 × 100 m 4 nages', 'M', 1, '2026-09-28 09:00:00', 90, 5000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(8, 3, 3, '-73 kg', 'H', 0, '2026-09-29 10:30:00', 90, 15000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(9, 3, 3, '-57 kg', 'F', 0, '2026-09-30 09:00:00', 120, 15000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(10, 3, 3, 'Par équipes', 'M', 1, '2026-10-01 17:00:00', 30, 15000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(11, 4, 4, 'Vitesse individuelle', 'F', 0, '2026-10-02 10:30:00', 120, 5000, '2026-09-30 13:44:30', '2026-09-30 13:44:29', '2026-09-30 13:44:30'),
(12, 4, 4, 'Keirin', 'H', 0, '2026-10-03 14:00:00', 75, 5000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(13, 5, 5, 'Fleuret individuel', 'F', 0, '2026-10-04 16:30:00', 30, 8000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(14, 5, 5, 'Épée individuelle', 'H', 0, '2026-10-05 20:00:00', 180, 8000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(15, 6, 3, 'Finale du tournoi', 'H', 1, '2026-10-06 21:00:00', 50, 15000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(16, 6, 3, 'Finale du tournoi', 'F', 1, '2026-10-07 11:00:00', 30, 15000, NULL, '2026-09-30 13:44:29', '2026-09-30 13:44:29'),
(17, 7, 5, 'Concours général', 'F', 0, '2026-10-08 09:30:00', 75, 8000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(18, 7, 5, 'Barre fixe', 'H', 0, '2026-10-09 20:30:00', 90, 8000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(19, 8, 7, 'Simple dames', 'F', 0, '2026-10-10 19:00:00', 30, 15000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(20, 8, 7, 'Simple messieurs', 'H', 0, '2026-09-22 13:00:00', 120, 15000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(21, 9, 6, 'Skiff', 'H', 0, '2026-09-23 13:30:00', 90, 14000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(22, 9, 6, 'Deux de couple', 'F', 0, '2026-09-24 11:00:00', 75, 14000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(23, 10, 8, 'Individuel', 'F', 0, '2026-09-25 20:00:00', 50, 8000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(24, 10, 8, 'Par équipes', 'M', 1, '2026-09-26 15:00:00', 50, 8000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(25, 11, 3, '-57 kg', 'F', 0, '2026-09-27 17:00:00', 30, 15000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(26, 11, 3, '-80 kg', 'H', 0, '2026-09-28 17:30:00', 75, 15000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(27, 12, 9, 'Shortboard', 'F', 0, '2026-09-29 19:00:00', 30, 1000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(28, 12, 9, 'Shortboard', 'H', 0, '2026-09-30 14:00:00', 75, 1000, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30');

-- athletes (192 lignes)
INSERT INTO "athletes" ("id", "first_name", "last_name", "gender", "country_id", "sport_id") VALUES
(1, 'Lenore', 'Luettgen', 'F', 12, 1),
(2, '주희', '서', 'F', 13, 1),
(3, '幹', '井高', 'F', 4, 1),
(4, 'Emilie', 'Jenkins', 'F', 2, 1),
(5, 'Luna', 'Saito', 'F', 10, 1),
(6, 'Jimena', 'Tirado', 'F', 11, 1),
(7, 'Michela', 'Morelli', 'F', 7, 1),
(8, 'Margaret', 'Lundberg', 'F', 14, 1),
(9, 'Connor', 'O''Kon', 'H', 12, 1),
(10, 'Brett', 'Goldner', 'H', 2, 1),
(11, '正业', '位', 'H', 3, 1),
(12, 'Nico', 'Ulrich', 'H', 8, 1),
(13, 'Jabari', 'Boyle', 'H', 6, 1),
(14, 'Franz', 'Ivarsson', 'H', 14, 1),
(15, 'Fiorenzo', 'Rizzo', 'H', 7, 1),
(16, '재호', '염', 'H', 13, 1),
(17, 'Victoria', 'Vigil', 'F', 11, 2),
(18, 'Amy', 'Jones', 'F', 5, 2),
(19, 'Camila', 'Cortês', 'F', 10, 2),
(20, 'Vincenza', 'Fontana', 'F', 7, 2),
(21, '娟', '杨', 'F', 3, 2),
(22, 'Nancy', 'Mattsson', 'F', 14, 2),
(23, 'Elva', 'Rohan', 'F', 6, 2),
(24, 'Janna', 'Zhou', 'F', 9, 2),
(25, '篤司', '江古田', 'H', 4, 2),
(26, 'Frédéric', 'Lemaire', 'H', 1, 2),
(27, 'Luis', 'Duarte', 'H', 11, 2),
(28, 'Torjus', 'Viken', 'H', 15, 2),
(29, 'Bernhard', 'Baier', 'H', 8, 2),
(30, 'Máximo', 'Tamoio', 'H', 10, 2),
(31, 'Hilbert', 'Holmgren', 'H', 14, 2),
(32, 'Abdullah', 'Crist', 'H', 12, 2),
(33, 'Marija', 'Naumann', 'F', 8, 3),
(34, '누리', '현', 'F', 13, 3),
(35, 'Lauren', 'Moore', 'F', 5, 3),
(36, 'Joséphine', 'Boutin', 'F', 1, 3),
(37, 'Manuela', 'González', 'F', 11, 3),
(38, 'Sarina', 'Funk', 'F', 2, 3),
(39, 'Gilda', 'Langworth', 'F', 6, 3),
(40, 'Asa', 'Johnson', 'F', 12, 3),
(41, 'Gilles', 'Blin', 'H', 1, 3),
(42, 'Salvatore', 'Valentini', 'H', 7, 3),
(43, 'Hans-Günter', 'Meister', 'H', 8, 3),
(44, 'Jaime', 'Serrano', 'H', 11, 3),
(45, 'Estêvão', 'Sandoval', 'H', 10, 3),
(46, 'Coleman', 'Donnelly', 'H', 12, 3),
(47, 'German', 'Abernathy', 'H', 6, 3),
(48, 'Florian', 'van Noordeloos', 'H', 9, 3),
(49, '민서', '추', 'F', 13, 4),
(50, '香織', '中島', 'F', 4, 4);
INSERT INTO "athletes" ("id", "first_name", "last_name", "gender", "country_id", "sport_id") VALUES
(51, 'Rosa', 'van der Veen', 'F', 9, 4),
(52, 'Kassandra', 'Treutel', 'F', 12, 4),
(53, '桂香', '齐', 'F', 3, 4),
(54, 'Adel', 'Bakken', 'F', 15, 4),
(55, 'Aurore', 'Raymond', 'F', 1, 4),
(56, 'Helen', 'Håkansson', 'F', 14, 4),
(57, 'Daniel', 'Valentín', 'H', 11, 4),
(58, 'Valdo', 'Esposito', 'H', 7, 4),
(59, 'Victor', 'Leroux', 'H', 1, 4),
(60, 'Otho', 'Wintheiser', 'H', 12, 4),
(61, 'Peder', 'Eriksson', 'H', 14, 4),
(62, 'Archibald', 'Windler', 'H', 6, 4),
(63, 'Inácio', 'Zambrano', 'H', 10, 4),
(64, 'Hasse', 'Christensen', 'H', 15, 4),
(65, 'Mireille', 'Kuvalis', 'F', 6, 5),
(66, 'Ruth', 'Price', 'F', 5, 5),
(67, 'Gloria', 'Schimmel', 'F', 2, 5),
(68, 'Eivor', 'Berglund', 'F', 14, 5),
(69, 'Heloise', 'Beltrão', 'F', 10, 5),
(70, '千代', '山岸', 'F', 4, 5),
(71, 'Giesela', 'Engelhardt', 'F', 8, 5),
(72, 'Marion', 'Sporer', 'F', 12, 5),
(73, 'Tiago', 'Salgado', 'H', 10, 5),
(74, 'Mattias', 'Eide', 'H', 15, 5),
(75, 'Brian', 'Keskin', 'H', 9, 5),
(76, 'Henri', 'Normand', 'H', 1, 5),
(77, 'Connor', 'Jones', 'H', 5, 5),
(78, 'Osman', 'Stahl', 'H', 8, 5),
(79, 'Preston', 'McGlynn', 'H', 12, 5),
(80, '翼', '村山', 'H', 4, 5),
(81, 'Suus', 'van Bergen', 'F', 9, 6),
(82, 'Kaycee', 'Hand', 'F', 12, 6),
(83, '선우', '남궁', 'F', 13, 6),
(84, 'Andréia', 'Gusmão', 'F', 10, 6),
(85, 'Jimena', 'Rodríquez', 'F', 11, 6),
(86, '桂香', '路', 'F', 3, 6),
(87, 'Margaux', 'Brunet', 'F', 1, 6),
(88, 'Hilma', 'Fahey', 'F', 2, 6),
(89, 'Saul', 'Koelpin', 'H', 6, 6),
(90, '인규', '안', 'H', 13, 6),
(91, 'Jari', 'Prins', 'H', 9, 6),
(92, 'Besnik', 'Nygaard', 'H', 15, 6),
(93, 'Kai-Uwe', 'Springer', 'H', 8, 6),
(94, 'Petrus', 'Jansson', 'H', 14, 6),
(95, 'Adrien', 'Pinto', 'H', 1, 6),
(96, 'Romeo', 'Russo', 'H', 7, 6),
(97, 'Gitta', 'Moser', 'F', 8, 7),
(98, 'Alva', 'Holmqvist', 'F', 14, 7),
(99, 'Neoma', 'Becker', 'F', 12, 7),
(100, 'Michelle', 'Seguin', 'F', 1, 7);
INSERT INTO "athletes" ("id", "first_name", "last_name", "gender", "country_id", "sport_id") VALUES
(101, '미정', '주', 'F', 13, 7),
(102, 'Lilyan', 'Aufderhar', 'F', 2, 7),
(103, 'Georgia', 'Graham', 'F', 5, 7),
(104, '明美', '西之園', 'F', 4, 7),
(105, 'Ralph', 'Andreasson', 'H', 14, 7),
(106, 'Meinhard', 'Binder', 'H', 8, 7),
(107, 'Emmanuel', 'Khan', 'H', 15, 7),
(108, 'Louis', 'Laurent', 'H', 1, 7),
(109, 'Márcio', 'Rios', 'H', 10, 7),
(110, 'Harry', 'Murray', 'H', 2, 7),
(111, 'Piersilvio', 'Lombardo', 'H', 7, 7),
(112, 'Fedde', 'Boers', 'H', 9, 7),
(113, 'Teresa', 'Llamas', 'F', 11, 8),
(114, 'Ann-Britt', 'Ström', 'F', 14, 8),
(115, '秀芳', '祁', 'F', 3, 8),
(116, 'Vita', 'McLaughlin', 'F', 12, 8),
(117, '春香', '渡辺', 'F', 4, 8),
(118, 'Heiderose', 'Bach', 'F', 8, 8),
(119, 'Emilia', 'Ferrari', 'F', 7, 8),
(120, '미영', '문', 'F', 13, 8),
(121, 'Aiden', 'Fox', 'H', 5, 8),
(122, 'José Manuel', 'Calvillo', 'H', 11, 8),
(123, 'Klaus', 'Schütte', 'H', 8, 8),
(124, 'Louis', 'Blot', 'H', 1, 8),
(125, 'Domenico', 'Barone', 'H', 7, 8),
(126, 'Maurício', 'Sepúlveda', 'H', 10, 8),
(127, '英樹', '山田', 'H', 4, 8),
(128, 'Colin', 'Dijkman', 'H', 9, 8),
(129, 'Stephany', 'Goldner', 'F', 12, 9),
(130, 'Katherine', 'Toledo', 'F', 10, 9),
(131, 'Amelia', 'Farah', 'F', 9, 9),
(132, '아린', '반', 'F', 13, 9),
(133, 'Cordia', 'Gulgowski', 'F', 2, 9),
(134, 'Monia', 'Pagano', 'F', 7, 9),
(135, 'Gaby', 'Popp', 'F', 8, 9),
(136, '桃子', '近藤', 'F', 4, 9),
(137, 'Ambros', 'Bøe', 'H', 15, 9),
(138, 'Juan', 'Lomeli', 'H', 11, 9),
(139, '建', '田', 'H', 3, 9),
(140, 'Éric', 'Hubert', 'H', 1, 9),
(141, 'Kamryn', 'Witting', 'H', 12, 9),
(142, '学', '山口', 'H', 4, 9),
(143, 'Dante', 'Sandoval', 'H', 10, 9),
(144, 'Costantino', 'Barbieri', 'H', 7, 9),
(145, 'Henny', 'Samuelsson', 'F', 14, 10),
(146, 'Beverly', 'Thiel', 'F', 12, 10),
(147, 'Aitana', 'Pastor', 'F', 11, 10),
(148, 'Hortense', 'Lemonnier', 'F', 1, 10),
(149, 'Suzanne', 'Paucek', 'F', 6, 10),
(150, 'Lilly', 'Kaiser', 'F', 8, 10);
INSERT INTO "athletes" ("id", "first_name", "last_name", "gender", "country_id", "sport_id") VALUES
(151, 'Heather', 'Volkman', 'F', 2, 10),
(152, 'Patricia', 'Butler', 'F', 5, 10),
(153, 'Matthieu', 'Charles', 'H', 1, 10),
(154, 'John', 'Murphy', 'H', 5, 10),
(155, 'Manfred', 'Forsberg', 'H', 14, 10),
(156, '涛', '栗', 'H', 3, 10),
(157, 'Benedito', 'Madeira', 'H', 10, 10),
(158, '대수', '이', 'H', 13, 10),
(159, 'Kian', 'Bosch', 'H', 9, 10),
(160, '直樹', '大垣', 'H', 4, 10),
(161, 'Imelda', 'Murazik', 'F', 6, 11),
(162, '淑华', '穆', 'F', 3, 11),
(163, 'Suzanne', 'Seguin', 'F', 1, 11),
(164, 'Stella', 'Ruggiero', 'F', 7, 11),
(165, 'さゆり', '山田', 'F', 4, 11),
(166, 'Lidia', 'Concepción', 'F', 11, 11),
(167, 'Marlen', 'Rohde', 'F', 8, 11),
(168, 'Therése', 'Nyberg', 'F', 14, 11),
(169, '修平', '高橋', 'H', 4, 11),
(170, 'Andrew', 'Simpson', 'H', 5, 11),
(171, 'Ernie', 'Mueller', 'H', 12, 11),
(172, 'Cletus', 'Boehm', 'H', 2, 11),
(173, 'Marzio', 'Giordano', 'H', 7, 11),
(174, 'Dino', 'Gundersen', 'H', 15, 11),
(175, 'Rafael', 'Paes', 'H', 10, 11),
(176, '영식', '임', 'H', 13, 11),
(177, 'Florida', 'Hansen', 'F', 12, 12),
(178, 'Kayleigh', 'Sanders', 'F', 9, 12),
(179, 'Danielle', 'Barbier', 'F', 1, 12),
(180, '秀梅', '龚', 'F', 3, 12),
(181, 'Meta', 'Trantow', 'F', 2, 12),
(182, 'Helena', 'Björk', 'F', 14, 12),
(183, 'Dena', 'Schuster', 'F', 6, 12),
(184, 'Andrea', 'Lozano', 'F', 11, 12),
(185, 'Rafael', 'Ponce', 'H', 11, 12),
(186, 'Kevin', 'Hughes', 'H', 5, 12),
(187, '直樹', '桐山', 'H', 4, 12),
(188, 'Egisto', 'Mancini', 'H', 7, 12),
(189, 'Kevin', 'Ünal', 'H', 9, 12),
(190, 'Ahmed', 'Sauer', 'H', 8, 12),
(191, 'Mark', 'Skiles', 'H', 2, 12),
(192, 'Otto', 'Kautzer', 'H', 6, 12);

-- results (55 lignes)
INSERT INTO "results" ("id", "event_id", "country_id", "athlete_id", "medal") VALUES
(1, 1, 7, 15, 'gold'),
(2, 1, 3, 11, 'silver'),
(3, 1, 12, 9, 'bronze'),
(4, 2, 11, 6, 'gold'),
(5, 2, 10, 5, 'silver'),
(6, 2, 14, 8, 'bronze'),
(7, 3, 14, 8, 'gold'),
(8, 3, 7, 7, 'silver'),
(9, 3, 12, 1, 'bronze'),
(10, 4, 6, 13, 'gold'),
(11, 4, 3, 11, 'silver'),
(12, 4, 14, 14, 'bronze'),
(13, 5, 14, 22, 'gold'),
(14, 5, 7, 20, 'silver'),
(15, 5, 9, 24, 'bronze'),
(16, 6, 8, 29, 'gold'),
(17, 6, 14, 31, 'silver'),
(18, 6, 10, 30, 'bronze'),
(19, 7, 4, NULL, 'gold'),
(20, 7, 9, NULL, 'silver'),
(21, 7, 14, NULL, 'bronze'),
(22, 8, 9, 48, 'gold'),
(23, 8, 6, 47, 'silver'),
(24, 8, 10, 45, 'bronze'),
(25, 8, 7, 42, 'bronze'),
(26, 9, 11, 37, 'gold'),
(27, 9, 12, 40, 'silver'),
(28, 9, 1, 36, 'bronze'),
(29, 9, 5, 35, 'bronze'),
(30, 20, 9, 128, 'gold'),
(31, 20, 11, 122, 'silver'),
(32, 20, 4, 127, 'bronze'),
(33, 21, 15, 137, 'gold'),
(34, 21, 12, 141, 'silver'),
(35, 21, 11, 138, 'bronze'),
(36, 22, 12, 129, 'gold'),
(37, 22, 8, 135, 'silver'),
(38, 22, 2, 133, 'bronze'),
(39, 23, 5, 152, 'gold'),
(40, 23, 8, 150, 'silver'),
(41, 23, 2, 151, 'bronze'),
(42, 24, 3, NULL, 'gold'),
(43, 24, 5, NULL, 'silver'),
(44, 24, 4, NULL, 'bronze'),
(45, 25, 6, 161, 'gold'),
(46, 25, 14, 168, 'silver'),
(47, 25, 8, 167, 'bronze'),
(48, 25, 7, 164, 'bronze'),
(49, 26, 13, 176, 'gold'),
(50, 26, 4, 169, 'silver');
INSERT INTO "results" ("id", "event_id", "country_id", "athlete_id", "medal") VALUES
(51, 26, 2, 172, 'bronze'),
(52, 26, 5, 170, 'bronze'),
(53, 27, 6, 183, 'gold'),
(54, 27, 14, 182, 'silver'),
(55, 27, 9, 178, 'bronze');

-- tickets (3 lignes)
INSERT INTO "tickets" ("id", "user_id", "event_id", "quantity", "unit_price", "cancelled_at", "created_at", "updated_at") VALUES
(1, 2, 28, 2, 75, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(2, 2, 10, 4, 30, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30'),
(3, 2, 11, 4, 120, NULL, '2026-09-30 13:44:30', '2026-09-30 13:44:30');

COMMIT;
PRAGMA foreign_keys = ON;
