-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 27 déc. 2024 à 16:14
-- Version du serveur : 10.4.25-MariaDB
-- Version de PHP : 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_appels`
--

-- --------------------------------------------------------

--
-- Structure de la table `cadeaux`
--

CREATE TABLE `cadeaux` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `cadeaux`
--

INSERT INTO `cadeaux` (`id`, `nom`) VALUES
(1, 'Anniversaire'),
(2, 'Mariage'),
(5, '1ère Communion');

-- --------------------------------------------------------

--
-- Structure de la table `receptions`
--

CREATE TABLE `receptions` (
  `id` int(11) NOT NULL,
  `date_saisie` datetime DEFAULT current_timestamp(),
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `cadeau_id` int(11) DEFAULT NULL,
  `texte` text DEFAULT NULL,
  `date_estimee_livraison` date DEFAULT NULL,
  `express` tinyint(1) DEFAULT 0,
  `prix` decimal(10,2) DEFAULT 0.00,
  `paye` tinyint(1) DEFAULT 0,
  `observation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `receptions`
--

INSERT INTO `receptions` (`id`, `date_saisie`, `nom`, `prenom`, `telephone`, `email`, `adresse`, `cadeau_id`, `texte`, `date_estimee_livraison`, `express`, `prix`, `paye`, `observation`) VALUES
(1, '2024-12-22 13:33:33', 'Fayettes', 'Nadège', '0611111125', 'nadege@gmail.com', '174 rue foch', 1, 'Anniversaire de son fil Adrien', '2024-12-28', 1, '10.00', 0, 'Happy Birthday Charlie 7ans'),
(2, '2024-12-22 14:38:42', 'BEAUVAIS', 'DOM', '0712458854', 'dom@gmail.com', '5 rues des augustins Metz', 2, 'Heureux Mariage', '2025-01-04', 0, '25.00', 1, 'En espèce'),
(3, '2024-12-22 17:12:35', 'BLIMPO', 'Bazil', '0658458512', 'bazil@gmail.com', 'Metz Nors 174 rue luxe', 1, 'HBD Cherie', '2024-12-24', 1, '15.00', 1, 'Chèque'),
(4, '2024-12-22 19:39:19', 'Nahima', 'shakira', '0722458558', 'nahima@orange.fr', '45 rue giraumont', 5, '', '2024-12-28', 0, '30.00', 1, 'Chèque');

-- --------------------------------------------------------

--
-- Structure de la table `traitements`
--

CREATE TABLE `traitements` (
  `id` int(11) NOT NULL,
  `reception_id` int(11) NOT NULL,
  `statut` enum('en cours','clôturé','annulé') NOT NULL DEFAULT 'en cours',
  `date_traitement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `traitements`
--

INSERT INTO `traitements` (`id`, `reception_id`, `statut`, `date_traitement`) VALUES
(1, 2, 'clôturé', '2024-12-22 15:15:16'),
(2, 1, 'annulé', '2024-12-22 16:13:36'),
(3, 2, 'en cours', '2024-12-22 16:13:45'),
(4, 2, 'clôturé', '2024-12-22 16:14:21'),
(5, 3, 'en cours', '2024-12-22 17:21:12'),
(6, 2, 'en cours', '2024-12-22 17:42:32'),
(7, 2, 'clôturé', '2024-12-22 18:00:38'),
(8, 4, 'en cours', '2024-12-22 18:41:21'),
(9, 4, 'clôturé', '2024-12-22 18:42:13');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','agent') NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `remember_token`) VALUES
(1, 'sam@gmail.com', '$2y$10$dOEOzr527UJQJLuWOUXUMe78.IUPK98srD2WjH1kOW8od2hWR5nuu', 'admin', '058bfa745f3c3da9e7a8df9f9d9383c2'),
(3, 'olivier@gmail.com', '$2y$10$dOEOzr527UJQJLuWOUXUMe78.IUPK98srD2WjH1kOW8od2hWR5nuu', 'agent', 'a73e19d73d28ddbfbec07cb952481060'),
(5, 'brayan@gmail.com', '$2y$10$OLMVxx10Yp8ZhBoc7Vjgb.Yet/d6sJ4Dp3F51162MgrkpQrqYSHtC', 'agent', 'c7fb23629bbcd698cea756ca3df956f8');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `cadeaux`
--
ALTER TABLE `cadeaux`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `receptions`
--
ALTER TABLE `receptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cadeau_id` (`cadeau_id`);

--
-- Index pour la table `traitements`
--
ALTER TABLE `traitements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reception_id` (`reception_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `cadeaux`
--
ALTER TABLE `cadeaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `receptions`
--
ALTER TABLE `receptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `traitements`
--
ALTER TABLE `traitements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `receptions`
--
ALTER TABLE `receptions`
  ADD CONSTRAINT `receptions_ibfk_1` FOREIGN KEY (`cadeau_id`) REFERENCES `cadeaux` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `traitements`
--
ALTER TABLE `traitements`
  ADD CONSTRAINT `traitements_ibfk_1` FOREIGN KEY (`reception_id`) REFERENCES `receptions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
