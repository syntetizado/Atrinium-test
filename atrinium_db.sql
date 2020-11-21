-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-11-2020 a las 09:14:27
-- Versión del servidor: 10.4.16-MariaDB
-- Versión de PHP: 7.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `atrinium_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company`
--

CREATE TABLE `company` (
  `id` int(11) NOT NULL,
  `id_sector` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `company`
--

INSERT INTO `company` (`id`, `id_sector`, `name`, `phone`, `email`) VALUES
(2, 4, 'volatile company', '168546384352', 'ashda@asdhbasd.es'),
(3, 7, 'volatile companysdfsdfsd', '168546384352', 'ashda@asdhbasd.es'),
(4, 7, 'volatile csdsd cacs', '168546384352', 'ashda@asdhbasd.es'),
(5, 9, 'volatile company', '168546384352', 'ashda@asdhbasd.es'),
(6, 4, 'volatile company', '168546384352', 'ashda@asdhbasd.es'),
(7, 7, 'volatile companysdfsdfsd', '168546384352', 'ashda@asdhbasd.es'),
(8, 4, 'volatile csdsd cacs', '168546384352', 'ashda@asdhbasd.es'),
(9, 7, 'volatile company', '168546384352', 'ashda@asdhbasd.es'),
(10, 7, 'volatile company', '168546384352', 'ashda@asdhbasd.es'),
(11, 7, 'volatile companysdfsdfsd', '168546384352', 'ashda@asdhbasd.es'),
(12, 4, 'volatile csdsd cacs', '168546384352', 'ashda@asdhbasd.es'),
(13, 4, 'volatile company', '168546384352', 'ashda@asdhbasd.es'),
(14, 18, 'wow', '232645', 'email@example.es');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `codename` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `role`
--

INSERT INTO `role` (`id`, `codename`, `name`) VALUES
(1, 'ROLE_USER', 'User'),
(2, 'ROLE_ADMIN', 'Administrator');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sector`
--

CREATE TABLE `sector` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `sector`
--

INSERT INTO `sector` (`id`, `name`) VALUES
(3, 'testSector3'),
(4, 'testSector4'),
(5, 'testSector5'),
(6, 'testSector6'),
(7, 'testSector7'),
(8, 'testSector8'),
(9, 'testSector9'),
(10, 'testSector10'),
(11, 'testSector11'),
(12, 'testSector12'),
(13, 'testSector13'),
(14, 'brrr'),
(15, 'brrr df'),
(17, 'sdfs df'),
(18, 'dfgd'),
(19, 'dfgdsad');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `id_role` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `user`
--

INSERT INTO `user` (`id`, `id_role`, `username`, `email`, `password`, `is_active`) VALUES
(2, 1, 'frankiss.pc', 'fran@kiss.pc', '$2y$04$dyLDhpDjghl404aCWq.7meRrts/6FK7X3YEkxH8Dt9ygVBaJ.Jjre', 1),
(3, 2, 'syntetizado', 'frankiss.pc@gmail.com', '$2y$04$QhuGK6UTrrco8sEzyqGCLef8oNhg5zER/TYQDIVBMtTHUSdZOIgva', 1),
(4, 2, 'asdasdasdas', 'frankasdiss.pc@gmail.com', '$2y$04$CX5347fUrqQy/iWNpEs0cOyzc3I1GiSL2YKhELiG.ZZUQs.8wIxG6', 1),
(5, 2, 'asdasdasdasasd', 'frankassddiss.pc@gmail.com', '$2y$04$PZZOORSpTrs.9OimU3hMeOAhQqYdm5RWMnO2CNrCSvYJZpB.WJYba', 1),
(7, 2, 'asdasdasdasasdsdfsdf', 'frankaasdasdssddiss.pc@gmail.com', '$2y$04$0u07MVIYkvBRE/a2Morf/ulda.5K45Ow60QUiJGLXkrH03pcn0yqy', 1),
(8, 2, 'asdasd', 'ads.pc@gmail.com', '$2y$04$GzldX1Ndazx.YLOPBqfwAOEFZJtN06iq7p1ppvSttF8Uvz9AE8uKm', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_sector`
--

CREATE TABLE `user_sector` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_sector` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `user_sector`
--

INSERT INTO `user_sector` (`id`, `id_user`, `id_sector`) VALUES
(1, 3, 4),
(2, 3, 9),
(3, 8, 11);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_sector_fk` (`id_sector`);

--
-- Indices de la tabla `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sector`
--
ALTER TABLE `sector`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_role_fk` (`id_role`);

--
-- Indices de la tabla `user_sector`
--
ALTER TABLE `user_sector`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_sector_fk_usersector` (`id_sector`),
  ADD KEY `id_user_fk_usersector` (`id_user`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `company`
--
ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `sector`
--
ALTER TABLE `sector`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `user_sector`
--
ALTER TABLE `user_sector`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `company`
--
ALTER TABLE `company`
  ADD CONSTRAINT `id_sector_fk` FOREIGN KEY (`id_sector`) REFERENCES `sector` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `id_role_fk` FOREIGN KEY (`id_role`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `user_sector`
--
ALTER TABLE `user_sector`
  ADD CONSTRAINT `id_sector_fk_usersector` FOREIGN KEY (`id_sector`) REFERENCES `sector` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `id_user_fk_usersector` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
