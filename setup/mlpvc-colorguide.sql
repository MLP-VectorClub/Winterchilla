SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

DROP DATABASE `mlpvc-colorguide`;
CREATE DATABASE `mlpvc-colorguide` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `mlpvc-colorguide`;

CREATE TABLE `colorgroups` (
  `groupid` int(11) NOT NULL,
  `ponyid` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `order` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `colorgroups` (`groupid`, `ponyid`, `label`, `order`) VALUES
(17, 1, 'Coat', 0),
(18, 1, 'Mane & Tail', 0),
(19, 1, 'Iris', 0),
(20, 1, 'Cutie Mark', 0),
(21, 1, 'Magic', 0),
(22, 11, 'Coat', 0),
(23, 11, 'Mane & Tail', 0),
(24, 11, 'Iris', 0),
(25, 11, 'Cutie Mark', 0),
(26, 12, 'Coat', 0),
(27, 12, 'Mane & Tail', 1),
(28, 12, 'Iris', 2),
(29, 12, 'Magic', 4),
(30, 12, 'Cutie Mark', 3),
(35, 13, 'Coat', 0),
(36, 13, 'Mane & Tail', 1),
(37, 13, 'Iris ', 2),
(38, 13, 'Cutie Mark', 3),
(39, 13, 'Magic', 4),
(40, 14, 'Coat', 0),
(41, 14, 'Mane & Tail', 1),
(42, 14, 'Iris', 2),
(43, 14, 'Cutie Mark', 3),
(44, 14, 'Bandana', 6),
(45, 14, 'Magic', 4),
(46, 14, 'Glasses', 5),
(47, 15, 'Coat', 0),
(48, 15, 'Mane & Tail', 1),
(49, 15, 'Iris', 2),
(51, 15, 'Dress', 4),
(52, 15, 'Saddle', 5),
(53, 15, 'Magic', 3),
(54, 2, 'Coat', 0),
(55, 2, 'Mane & Tail', 1),
(56, 2, 'Iris', 2),
(57, 2, 'Cutie Mark', 3),
(58, 2, 'Hat & Hair Tie', 4),
(59, 4, 'Coat', 0),
(60, 4, 'Mane & Tail', 1),
(61, 4, 'Iris', 2),
(62, 4, 'Cutie Mark', 3),
(63, 3, 'Coat', 0),
(64, 3, 'Mane & Tail', 1),
(65, 3, 'Iris', 2),
(66, 3, 'Cutie Mark', 3),
(67, 5, 'Coat', 0),
(68, 5, 'Mane & Tail', 1),
(69, 5, 'Iris', 2),
(70, 5, 'Cutie Mark', 3),
(71, 6, 'Coat', 0),
(72, 6, 'Mane & Tail', 1),
(73, 6, 'Iris', 2),
(74, 6, 'Cutie Mark', 3),
(75, 7, 'Body', 0),
(76, 7, 'Spikes', 1),
(77, 7, 'Iris', 3),
(78, 7, 'Ears', 2),
(79, 7, 'Mouth', 0),
(80, 6, 'Magic', 4),
(81, 10, 'Coat', 0),
(82, 10, 'Mane & Tail', 0),
(83, 10, 'Iris', 0),
(84, 10, 'Cutie Mark', 0),
(85, 16, 'Coat', 0),
(86, 16, 'Mane & Tail', 1),
(87, 16, 'Iris', 2),
(88, 16, 'Cutie Mark', 3),
(89, 17, 'Coat', 0),
(90, 17, 'Mane & Tail', 1),
(91, 17, 'Iris', 2),
(92, 17, 'Cutie Mark', 3),
(93, 18, 'Coat', 0),
(94, 18, 'Mane & Tail', 1),
(95, 18, 'Iris', 2),
(96, 18, 'Cutie Mark', 3),
(97, 18, 'Wrap', 4),
(98, 19, 'Coat', 0),
(99, 19, 'Mane & Tail', 1),
(100, 19, 'Iris', 2),
(101, 19, 'Cutie Mark', 3),
(102, 20, 'Coat', 0),
(103, 20, 'Mane & Tail', 1),
(104, 20, 'Iris', 2),
(105, 20, 'Cutie Mark', 3),
(106, 20, 'Earrings/Necklace', 5),
(107, 20, 'Sweater', 4),
(108, 21, 'Coat', 0),
(109, 21, 'Mane & Tail', 1),
(110, 21, 'Iris', 2),
(111, 21, 'Cutie Mark', 3),
(112, 21, 'Jacket', 5),
(113, 21, 'Scarf', 6),
(114, 21, 'Badge', 7),
(115, 21, 'Teeth', 4),
(116, 22, 'Coat', 0),
(117, 22, 'Mane & Tail', 1),
(118, 22, 'Iris', 2),
(119, 22, 'Cutie Mark', 3),
(120, 22, 'Magic', 4),
(121, 22, 'Hair Tie', 5),
(122, 23, 'Coat', 0),
(123, 23, 'Mane & Tail', 1),
(124, 23, 'Iris', 2),
(126, 24, 'Coat', 0),
(127, 24, 'Mane & Tail', 1),
(128, 24, 'Iris', 2),
(130, 23, 'Bow', 3),
(131, 25, 'Coat', 0),
(132, 25, 'Mane & Tail', 1),
(133, 25, 'Iris', 2),
(135, 26, 'Coat', 0),
(136, 26, 'Mane & Tail', 1),
(137, 26, 'Iris', 2),
(138, 26, 'Cutie Mark', 3),
(139, 27, 'Coat', 0),
(140, 27, 'Mane & Tail', 1),
(141, 27, 'Iris', 2),
(142, 27, 'Cutie Mark', 3),
(143, 27, 'Scarf', 4),
(144, 28, 'Coat', 0),
(145, 28, 'Mane & Tail', 1),
(146, 28, 'Iris', 2),
(147, 28, 'Cutie Mark', 3),
(164, 29, 'Coat', 0),
(165, 29, 'Mane & Tail', 1),
(166, 29, 'Iris', 2),
(167, 29, 'Cutie Mark', 3),
(168, 29, 'Magic', 4),
(169, 30, 'Coat', 0),
(170, 30, 'Mane & Tail', 1),
(171, 30, 'Iris', 2),
(172, 30, 'Cutie Mark', 3),
(173, 30, 'Neckpiece', 4),
(174, 30, 'Flower', 5),
(175, 9, 'Coat', 0),
(176, 9, 'Mane & Tail', 1),
(177, 9, 'Shoes', 4),
(178, 9, 'Regalia', 5),
(179, 9, 'Cutie Mark', 3),
(180, 9, 'Iris', 2),
(182, 31, 'Coat', 0),
(183, 31, 'Mane & Tail', 1),
(184, 31, 'Iris', 2),
(185, 31, 'Cutie Mark', 3),
(186, 31, 'Scarf', 4),
(187, 31, 'Headband', 5),
(188, 17, 'Magic', 4),
(189, 10, 'Magic ', 0),
(190, 16, 'Magic', 4);

CREATE TABLE `colors` (
  `colorid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `label` tinytext NOT NULL,
  `hex` varchar(7) DEFAULT NULL,
  `order` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `colors` (`colorid`, `groupid`, `label`, `hex`, `order`) VALUES
(55, 17, 'Outline', '#A46BBD', 0),
(57, 17, 'Fill', '#CC9CDF', 1),
(58, 17, 'Shadow Outline', '#9156A9', 2),
(59, 17, 'Shadow Fill', '#BF89D1', 3),
(60, 18, 'Outline and Inner Lines', '#132248', 0),
(61, 18, 'Fill', '#243870', 1),
(62, 18, 'Stripe 1', '#652D87', 2),
(63, 18, 'Stripe 2', '#EA428B', 3),
(64, 19, 'Gradient Top', '#1E093C', 0),
(65, 19, 'Gradient Bottom', '#662F89', 1),
(66, 19, 'Highlight Top', '#8D5DA4', 2),
(67, 19, 'Highlight Bottom', '#CCB2D3', 3),
(68, 20, 'Large Star', '#EA428B', 0),
(69, 20, 'Small Stars', '#FFFFFF', 1),
(70, 21, 'Aura', '#EA428B', 0),
(71, 22, 'Outline', '#7F859F', 0),
(72, 22, 'Fill', '#B5BBC7', 1),
(73, 22, 'Shadow Fill', '#ADB1BE', 2),
(74, 23, 'Outline', '#D6CD6B', 0),
(75, 23, 'Fill', '#E7E6A7', 1),
(76, 24, 'Gradient Top', '#D1973D', 0),
(77, 24, 'Gradient Bottom', '#D9E985', 1),
(78, 24, 'Highlight Top', '#D9E985', 2),
(79, 24, 'Highlight Bottom', '#E1EBB0', 3),
(80, 25, 'Bubble', '#CECEEA', 0),
(81, 25, 'Bubble Highlight', '#D5E9F0', 1),
(82, 26, 'Outline', '#3EA679', 0),
(83, 26, 'Fill', '#8DEBCD', 1),
(84, 26, 'Shadow Fill', '#6ACDA9', 2),
(85, 27, 'Outline 1', '#7AD9D7', 0),
(86, 27, 'Fill 1', '#EBEBEB', 1),
(87, 27, 'Outline 2', '#55C1C1', 2),
(88, 27, 'Fill 2', '#A5D7D7', 3),
(89, 28, 'Gradient Top', '#D56315', 0),
(90, 28, 'Gradient Bottom', '#E3C752', 1),
(91, 28, 'Highlight Top', '#EAEA6B', 2),
(92, 28, 'Highlight Bottom', '#ECECCF', 3),
(93, 29, 'Aura', '#C49159', 0),
(94, 30, 'Outline', '#AF821A', 0),
(95, 30, 'Fill', '#E1B046', 1),
(96, 30, 'Highlight', '#F2D297', 2),
(97, 30, 'Strings', '#50B18C', 3),
(111, 35, 'Outline', '#81CFBD', 0),
(112, 35, 'Fill', '#A1FFE9', 1),
(114, 35, 'Shadow Fill', '#90E4D0', 2),
(115, 36, 'Outline', '#80D940', 0),
(116, 36, 'Fill', '#D6FF58', 1),
(117, 37, 'Gradient Top', '#A84E0D', 0),
(119, 37, 'Gradient Bottom', '#EE8B2D', 1),
(120, 37, 'Highlight Top', '#F2AA75', 2),
(121, 37, 'Highlight Bottom', '#F5B98D', 3),
(122, 38, 'Main Star Fills', '#FFFFFF', 0),
(123, 38, 'Small Star Fills', '#D78F4F', 1),
(124, 37, 'Eyeball', '#F9FFCB', 4),
(125, 38, 'Head Outline', '#76C1AD', 2),
(126, 38, 'Head Fill', '#BEFFF0', 3),
(127, 38, 'Head Shadow', '#92DECF', 4),
(128, 39, 'Aura', '#E69A44', 0),
(129, 40, 'Outline', '#1D4555', 0),
(130, 40, 'Fill', '#2F7A98', 1),
(131, 40, 'Shadow Outline', '#203A45', 2),
(132, 40, 'Shadow Fill', '#2D5B6C', 3),
(133, 41, 'Outline', '#5E5A5A', 0),
(134, 41, 'Fill 1', '#B0B0B0', 1),
(135, 42, 'Gradient Top', '#2F8CB0', 0),
(137, 42, 'Gradient Bottom', '#ADD7E6', 1),
(140, 43, 'Stars', '#EED255', 0),
(142, 41, 'Fill 2', '#797979', 2),
(143, 44, 'Outline', '#9F1D3A', 0),
(144, 44, 'Main Fill', '#D44350', 1),
(145, 44, 'Thick Stripe', '#B1374F', 2),
(146, 44, 'Thin Stripe', '#E49355', 3),
(147, 45, 'Aura', '#F7DF53', 0),
(148, 46, 'Frame Outline', '#919DA4', 0),
(149, 46, 'Frame Fill', '#FFFFFF', 1),
(150, 46, 'Lens Top (85% Opacity)', '#260F1C', 2),
(151, 46, 'Lens Bottom (85% Opacity)', '#C06588', 3),
(152, 47, 'Main Outline', '#4596B0', 0),
(153, 47, 'Fill', '#B1EAFD', 2),
(156, 48, 'Outline', '#A3413E', 0),
(157, 48, 'Main Outer Fill', '#EB7F51', 1),
(158, 49, 'Gradient Top', '#F5973C', 0),
(160, 49, 'Gradient Bottom', '#F5B27D', 1),
(161, 49, 'Highlight Top', '#FADBAB', 2),
(162, 49, 'Highlight Bottom', '#FBEDD4', 3),
(165, 47, 'Inside Outlines (Ear, horn)', '#57ACD4', 1),
(166, 48, 'Outer Fill 1', '#E9D271', 2),
(167, 48, 'Outer Fill 2', '#FFAC89', 3),
(168, 48, 'Outer Fill 3 (at front)', '#EA5E5E', 4),
(169, 48, 'Inside Outline Stroke', '#95346D', 5),
(170, 48, 'Inside Fill Gradient Top', '#CE53B9', 6),
(171, 48, 'Inside Fill Gradient Bottom', '#88387D', 7),
(172, 48, 'Inside Fill Line', '#75326F', 8),
(173, 51, 'Outline', '#1E1A24', 0),
(174, 51, 'Fill 1', '#5D4D68', 1),
(175, 51, 'Fill 2', '#3B2D45', 2),
(176, 51, 'Stud Fill 1', '#FFFCCB', 3),
(177, 51, 'Stud Fill 2', '#F4D05B', 4),
(178, 52, 'Outline', '#E8AD34', 0),
(179, 52, 'Fill 1', '#F5CF5D', 1),
(180, 52, 'Fill 2', '#F9E457', 2),
(181, 52, 'Fill 3', '#FAE356', 3),
(182, 52, 'Seat fill', '#FEFBAA', 4),
(183, 52, 'Seat Decoration Fill 1', '#8F35B4', 5),
(184, 52, 'Seat Decoration Fill 2', '#AC5DCB', 6),
(185, 52, 'Stud Fill 1', '#FFFCCB', 7),
(186, 52, 'Stud Fill 2', '#F4D05B', 8),
(187, 53, 'Aura', '#F6EF95', 0),
(188, 49, 'Eyeshadow', '#564C9A', 4),
(189, 54, 'Outline', '#EF6F2F', 0),
(190, 54, 'Fill', '#FABA62', 1),
(192, 54, 'Shadow Fill', '#F0AA52', 2),
(193, 55, 'Outline', '#E7D462', 0),
(194, 55, 'Fill', '#FAF5AB', 1),
(195, 56, 'Gradient Top', '#287916', 0),
(197, 56, 'Gradient Bottom', '#61BA4E', 1),
(198, 56, 'Highlight Top', '#78D863', 2),
(199, 56, 'Highlight Bottom', '#CAECC4', 3),
(200, 57, 'Apples', '#EC3F41', 0),
(201, 57, 'Leaves', '#6BB944', 1),
(202, 58, 'Outline', '#B2884D', 0),
(203, 58, 'Fill', '#CA9A56', 1),
(204, 59, 'Outline', '#E880B0', 0),
(205, 59, 'Fill', '#F5B7D0', 1),
(206, 59, 'Shadow Outline', '#DD6FA4', 2),
(207, 59, 'Shadow Fill', '#E89CBF', 3),
(208, 60, 'Outline', '#BB1C76', 0),
(209, 60, 'Fill', '#EB458B', 1),
(210, 61, 'Gradient Top', '#196E91', 0),
(212, 61, 'Gradient Bottom', '#7DD0F1', 1),
(213, 61, 'Highlight Top', '#9CDCF4', 2),
(214, 61, 'Highlight Bottom', '#DCF3FD', 3),
(215, 62, 'Color 1', '#7ED0F2', 0),
(216, 62, 'Color 2', '#FAF5AB', 1),
(217, 63, 'Outline', '#E9D461', 0),
(218, 63, 'Fill', '#FAF5AB', 1),
(220, 63, 'Shadow Fill', '#F3E488', 2),
(221, 64, 'Outline', '#E581B1', 0),
(222, 64, 'Fill', '#F3B5CF', 1),
(223, 65, 'Gradient Top', '#02534D', 0),
(225, 65, 'Gradient Bottom', '#02ACA4', 1),
(226, 65, 'Highlight Top', '#3CBEB7', 2),
(227, 65, 'Highlight Bottom', '#84D2D4', 3),
(228, 66, 'Wings', '#F3B5CF', 0),
(229, 66, 'Body', '#69C8C3', 1),
(230, 67, 'Outline', '#6BABDA', 0),
(231, 67, 'Fill', '#9BDBF5', 1),
(233, 67, 'Shadow Fill', '#88C4E9', 2),
(234, 68, 'Outline/Blue Fill', '#1B98D1', 0),
(235, 68, 'Red Fill', '#EC4141', 1),
(236, 69, 'Gradient Top', '#580D36', 0),
(238, 69, 'Gradient Bottom', '#BC1D75', 1),
(239, 69, 'Highlight Top', '#D9539D', 2),
(240, 69, 'Highlight Bottom', '#FCB6DF', 3),
(241, 70, 'Cloud Outline/Blue Streak', '#1B98D1', 0),
(242, 70, 'Red Streak', '#EC4141', 2),
(243, 68, 'Orange Fill', '#EF7135', 2),
(244, 68, 'Yellow Fill', '#FAF5AB', 3),
(245, 68, 'Green Fill', '#5FBB4E', 4),
(246, 68, 'Purple Fill', '#632E86', 5),
(247, 70, 'Yellow Streak', '#FDE85F', 3),
(248, 71, 'Outline', '#BDC1C2', 0),
(249, 71, 'Fill', '#EAEEF0', 1),
(251, 71, 'Shadow Fill', '#DFE4E3', 2),
(252, 72, 'Outline/Gradient Dark Fill', '#4A1767', 0),
(253, 72, 'Solid Fill', '#5E50A0', 2),
(254, 73, 'Gradient Top', '#20476B', 0),
(256, 73, 'Gradient Bottom', '#3977B8', 1),
(257, 73, 'Highlight Top', '#5693CF', 2),
(258, 73, 'Highlight Bottom', '#76ADE5', 3),
(259, 74, 'Strokes', '#2696CB', 0),
(260, 74, 'Fill', '#7DD1F5', 1),
(263, 72, 'Gradient Light Fill', '#794897', 1),
(264, 73, 'Eyeshadow', '#B8E1F0', 4),
(265, 75, 'Purple Outline', '#985E9F', 0),
(266, 75, 'Purple Fill', '#C290C6', 1),
(267, 75, 'Green Outline', '#96CE7D', 2),
(268, 75, 'Green Fill', '#D5EBAD', 3),
(269, 76, 'Outline', '#2E992E', 0),
(270, 76, 'Fill', '#50C356', 1),
(271, 77, 'Gradient Top', '#277915', 0),
(273, 77, 'Gradient Bottom', '#5EBA4A', 1),
(274, 77, 'Highlight Top', '#77D963', 2),
(275, 77, 'Highlight Bottom', '#CAECC3', 3),
(276, 78, 'Outline', '#DCF188', 0),
(277, 78, 'Fill', '#AFD95E', 1),
(278, 75, 'Soles', '#AF72B6', 4),
(279, 79, 'Teeth', '#94B5B3', 0),
(280, 79, 'Tongue', '#F997C8', 1),
(281, 79, 'Mouth', '#973365', 2),
(282, 80, 'Aura', '#82C1DC', 0),
(283, 81, 'Outline', '#457BBB', 0),
(284, 81, 'Fill', '#81C3EA', 1),
(285, 81, 'Shadow Fill', '#7ABAE5', 2),
(286, 82, 'Dark Outline', '#303296', 0),
(287, 82, 'Dark Fill', '#393CB0', 1),
(288, 82, 'Light Outline', '#9E9FDC', 2),
(289, 82, 'Light Fill', '#CED0EC', 3),
(290, 83, 'Gradient Top', '#1F205D', 0),
(291, 83, 'Gradient Bottom', '#526BCB', 1),
(292, 83, 'Highlight Top', '#AAAAF8', 2),
(293, 83, 'Highlight Bottom', '#DBDCFC', 3),
(294, 84, 'Base', '#DBBF0F', 0),
(295, 84, 'Base Highlight', '#FBEB98', 1),
(296, 84, 'Glass', '#BFEAF8', 2),
(297, 84, 'Sand', '#D4CF97', 3),
(298, 85, 'Outline', '#BEB789', 0),
(299, 85, 'Fill', '#F9FAED', 1),
(301, 85, 'Shadow Fill', '#E0DEC1', 2),
(302, 86, 'Outline', '#DE83CD', 0),
(303, 86, 'Fill', '#F8AAE5', 1),
(304, 87, 'Gradient Top', '#015B56', 0),
(306, 87, 'Gradient Bottom', '#6BF0ED', 1),
(307, 87, 'Highlight Top', '#B1F0F4', 2),
(308, 87, 'Highlight Bottom', '#D5F9FB', 3),
(309, 88, 'Stars', '#B6E0F7', 0),
(311, 89, 'Outline', '#EFBD3B', 0),
(312, 89, 'Fill', '#FBFC63', 1),
(314, 89, 'Shadow Fill', '#F7E253', 2),
(315, 90, 'Outline', '#3695B5', 0),
(316, 90, 'Fill 1', '#68B5CF', 1),
(317, 91, 'Gradient Top', '#580D36', 0),
(319, 91, 'Gradient Bottom', '#BC1D75', 1),
(320, 91, 'Highlight Top', '#D9539D', 2),
(321, 91, 'Highlight Bottom', '#FCB6DF', 3),
(322, 92, 'Blue Outline', '#63B5CB', 0),
(323, 92, 'Blue Fill', '#A2D7E1', 1),
(324, 90, 'Fill 2', '#7BCBE1', 2),
(325, 92, 'Green Outline', '#96DC51', 2),
(326, 92, 'Green Fill', '#BAEF85', 3),
(327, 93, 'Outline', '#ACC849', 0),
(328, 93, 'Fill', '#D2EA91', 1),
(329, 93, 'Shadow Outline', '#A4C040', 2),
(330, 93, 'Shadow Fill', '#BDD56C', 3),
(331, 94, 'Outline', '#ADD9D5', 0),
(332, 94, 'Fill', '#FFFFFF', 1),
(333, 95, 'Gradient Top', '#EC565C', 0),
(335, 95, 'Gradient Bottom', '#F49A43', 1),
(338, 96, 'Plate Fill 1', '#9AA596', 0),
(339, 96, 'Plate Fill 2', '#A2AF9D', 1),
(340, 96, 'Plate Fill 3', '#B2BEAC', 2),
(341, 96, 'Pie FIll 1', '#B68145', 3),
(342, 96, 'Pie FIll 2', '#D29752', 4),
(343, 96, 'Pie FIll 3', '#E4A95A', 5),
(344, 96, 'Pie FIll 4', '#FBB963', 6),
(345, 97, 'Outline', '#F58950', 0),
(346, 97, 'Fill', '#FABA63', 1),
(347, 97, 'Apples', '#EB575A', 2),
(348, 97, 'Stems', '#7CA42A', 3),
(349, 97, 'Frill Outline', '#ADD9D5', 4),
(350, 97, 'Frill Fill', '#FFFFFF', 5),
(351, 98, 'Outline', '#3DBAC9', 0),
(352, 98, 'Fill', '#8BD8DF', 1),
(354, 98, 'Shadow Fill', '#6ECAD5', 2),
(355, 99, 'Outline', '#A6E9F9', 0),
(356, 99, 'Fill', '#FFFFFF', 1),
(357, 100, 'Gradient Top', '#320141', 0),
(359, 100, 'Gradient Bottom', '#F990D1', 1),
(360, 100, 'Highlight Top', '#D53AB6', 2),
(361, 100, 'Highlight Bottom', '#FDCAE9', 3),
(362, 101, 'Air Wave', '#FCFF98', 0),
(363, 101, 'Horseshoe', '#3797C7', 1),
(364, 99, 'Blue Fill', '#D4F3FC', 2),
(365, 101, 'Horseshoe Dots', '#FFFFFF', 2),
(366, 102, 'Outline', '#D8971C', 0),
(367, 102, 'Fill', '#F3E365', 1),
(368, 102, 'Shadow Outline', '#C18719', 2),
(369, 102, 'Shadow Fill', '#DACB5A', 3),
(370, 103, 'Outline', '#AC240B', 0),
(371, 103, 'Fill 1', '#FA8A24', 1),
(372, 104, 'Gradient Top', '#B74729', 0),
(374, 104, 'Gradient Bottom', '#F48F66', 1),
(377, 105, 'Outline', '#AA4300', 0),
(378, 105, 'Fill 1', '#E37B00', 1),
(379, 103, 'Fill 2', '#DF5C0A', 2),
(380, 105, 'Fill 2', '#FDBF36', 2),
(381, 104, 'Eyeshadow', '#7FA2C3', 2),
(382, 106, 'Outline', '#75C0C8', 0),
(383, 106, 'Fill', '#DEF2F0', 1),
(384, 107, 'Outline', '#552E66', 0),
(385, 107, 'Fill 1', '#743E8A', 1),
(386, 107, 'Fill 2', '#A775BE', 2),
(387, 107, 'Shadow Outline', '#4A295A', 3),
(388, 107, 'Shadow Fill 1', '#69377D', 4),
(389, 107, 'Shadow Fill 2', '#9568AA', 5),
(390, 58, 'Hair Tie', '#EC3F41', 2),
(392, 70, 'Cloud Fill', '#FFFFFF', 1),
(394, 108, 'Outline', '#6BABDA', 0),
(395, 108, 'Fill', '#9CDBF5', 1),
(397, 108, 'Shadow Fill', '#89C7EB', 2),
(398, 109, 'Outline', '#52574C', 0),
(399, 109, 'Fill 1', '#8E9783', 1),
(400, 110, 'Gradient Top', '#CB9932', 0),
(402, 110, 'Gradient Bottom', '#FCEBA5', 1),
(405, 111, 'Helmet Fill 1', '#48412C', 0),
(406, 111, 'Helmet Fill 2', '#7C704C', 1),
(407, 109, 'Fill 2', '#777B6C', 2),
(408, 109, 'Shadow Fill', '#585B52', 3),
(409, 110, 'Eyebrows', '#53564D', 2),
(410, 112, 'Outline', '#324649', 0),
(411, 112, 'Fill 1', '#507276', 1),
(412, 112, 'Fill 2', '#A9AE82', 2),
(413, 112, 'Sleeve Patch', '#BEC99D', 3),
(414, 112, 'Zipper Outline', '#77ACA8', 4),
(415, 112, 'Zipper Fill', '#C2DFE3', 5),
(416, 112, 'Fur Outline', '#41585B', 6),
(417, 112, 'Fur Fill', '#AAAE82', 7),
(418, 113, 'Outline', '#D7D78C', 0),
(419, 113, 'Fill', '#FAFCEE', 1),
(420, 114, 'Outline', '#8E6E21', 0),
(421, 114, 'Fill 1', '#E2D23B', 1),
(422, 114, 'Fill 2', '#EFE89A', 2),
(423, 111, 'Helmet Fill 3', '#C1AE73', 2),
(424, 111, 'Helmet Shadow Fill', '#1E1D11', 3),
(425, 111, 'Strap Fill', '#629672', 4),
(426, 111, 'Strap Holes', '#35462A', 5),
(427, 111, 'Wing Outline', '#D3D8A0', 6),
(428, 111, 'Wing Fill', '#F7F8DC', 7),
(429, 111, 'Goggles Rim', '#313131', 8),
(430, 111, 'Goggles Fill 1', '#3AD0F5', 9),
(431, 111, 'Goggles Fill 2', '#D7F5FB', 10),
(432, 111, 'Goggles Fill 3 (At rim)', '#2BA9C4', 11),
(433, 115, 'Outline', '#A3C6BC', 0),
(434, 115, 'Fill', '#FFFFFF', 1),
(435, 116, 'Outline', '#FD80C6', 0),
(436, 116, 'Fill', '#FFC3E5', 1),
(437, 116, 'Shadow Outline', '#F673B8', 2),
(438, 116, 'Shadow Fill', '#FFB0D8', 3),
(439, 117, 'Outline', '#533251', 0),
(440, 117, 'Fill', '#955495', 1),
(441, 118, 'Gradient Top', '#681D46', 0),
(443, 118, 'Gradient Bottom', '#CD2F89', 1),
(444, 118, 'Highlight Top', '#EB64B1', 2),
(445, 118, 'Highlight Bottom', '#FFBFE7', 3),
(446, 119, 'Candy 1', '#FD81BA', 0),
(447, 119, 'Candy 2', '#FEAD89', 1),
(448, 119, 'Candy 3', '#FFD77C', 2),
(449, 119, 'Cup Top', '#7FC9C7', 4),
(450, 119, 'Cup Fill 1', '#A2E3BE', 5),
(451, 119, 'Cup Fill 2', '#BFF7C6', 6),
(452, 119, 'Cupcake Fill 1', '#C477C6', 7),
(453, 119, 'Cupcake Fill 2', '#E0A5E0', 8),
(454, 119, 'Cherry Fill', '#FD696D', 9),
(455, 119, 'Cherry Stroke', '#C8454F', 10),
(456, 119, 'Cherry Stem', '#57334E', 11),
(457, 119, 'Candy Highlight', '#FFFFFF', 3),
(458, 120, 'Magic', '#47C0CC', 0),
(459, 121, 'Outline 1', '#13A9A9', 0),
(460, 121, 'Fill 1', '#0FDAD9', 1),
(461, 121, 'Outline 2', '#89CBC8', 2),
(462, 121, 'Fill 2', '#D1FEEC', 3),
(463, 122, 'Outline', '#D9C574', 0),
(464, 122, 'Fill', '#F3F49B', 1),
(465, 122, 'Shadow Outline', '#D5C167', 2),
(466, 122, 'Shadow Fill', '#E6DC7F', 3),
(467, 123, 'Outline', '#C52452', 0),
(468, 123, 'Fill', '#F5415F', 1),
(469, 124, 'Gradient Top', '#ED585A', 0),
(471, 124, 'Gradient Bottom', '#FBA93F', 1),
(472, 124, 'Highlight Top', '#FCC657', 2),
(473, 124, 'Highlight Bottom', '#FEE27A', 3),
(476, 126, 'Outline', '#F37033', 0),
(477, 126, 'Fill', '#F9B764', 1),
(478, 126, 'Shadow Outline', '#EA6B2B', 2),
(479, 126, 'Shadow Fill', '#F0AA56', 3),
(480, 127, 'Outline', '#BD1F77', 0),
(481, 127, 'Fill', '#BF5D93', 1),
(482, 128, 'Gradient Top', '#482562', 0),
(484, 128, 'Gradient Bottom', '#B28EC0', 1),
(485, 128, 'Highlight Top', '#C5A6D0', 2),
(486, 128, 'Highlight Bottom', '#E7CEE4', 3),
(489, 130, 'Outline', '#C72965', 0),
(490, 130, 'Fill 1', '#F35F91', 1),
(491, 130, 'Fill 2', '#EC438C', 2),
(492, 131, 'Outline', '#CEC8D1', 0),
(493, 131, 'Fill', '#EFEDED', 1),
(495, 131, 'Shadow Fill', '#E0DDE3', 2),
(496, 132, 'Outline', '#785B88', 0),
(497, 132, 'Fill 1', '#B28DC1', 1),
(498, 133, 'Gradient Top', '#629558', 0),
(500, 133, 'Gradient Bottom', '#AED79E', 1),
(501, 133, 'Highlight Top', '#CBE4BE', 2),
(502, 133, 'Highlight Bottom', '#F4F8ED', 3),
(505, 132, 'Fill 2', '#F6B8D2', 2),
(506, 135, 'Outline', '#2F5173', 0),
(507, 135, 'Fill', '#4C7DAF', 1),
(509, 135, 'Shadow Fill', '#406C93', 2),
(510, 136, 'Outline', '#9BAAE8', 0),
(511, 136, 'Fill 1', '#E1E6FA', 1),
(512, 137, 'Gradient Top', '#1378AB', 0),
(513, 137, 'Gradient Middle', '#47CFFF', 1),
(514, 137, 'Gradient Bottom', '#6EDCFF', 2),
(515, 137, 'Highlight Top', '#90E2FF', 3),
(516, 137, 'Highlight Bottom', '#D5F3FF', 4),
(517, 138, 'Moon 1', '#385E83', 0),
(518, 138, 'Moon 2', '#FDFDCB', 1),
(519, 138, 'Feather Center', '#D6E4FF', 2),
(520, 138, 'Feather Fill 1', '#97ABEB', 3),
(521, 138, 'Feather Fill 2', '#6A8AD2', 4),
(522, 138, 'Feather Fill 3', '#44608D', 5),
(523, 136, 'Fill 2', '#C4CFF4', 2),
(524, 136, 'Fill 3', '#FFFFFF', 3),
(525, 139, 'Outline', '#BFD1E1', 0),
(526, 139, 'Fill', '#FFFFFF', 1),
(527, 139, 'Shadow Outline', '#B2C7D2', 2),
(528, 139, 'Shadow Fill', '#D6E3E5', 3),
(529, 140, 'Outline', '#A6BFD8', 0),
(530, 140, 'Fill 1', '#F2FBFC', 1),
(531, 141, 'Gradient Top', '#2B4E99', 0),
(532, 141, 'Gradient Middle', '#6E9FFC', 1),
(533, 141, 'Gradient Bottom', '#B0EBFF', 2),
(536, 142, 'Fill', '#87B1E6', 0),
(537, 142, 'Inner Strokes', '#BEDBFA', 1),
(538, 140, 'Fill 2', '#FFFFFF', 2),
(539, 143, 'Outline', '#603860', 0),
(540, 143, 'Fill', '#9B579B', 1),
(541, 144, 'Outline', '#75C3D3', 0),
(542, 144, 'Fill', '#C7F0F2', 1),
(544, 144, 'Shadow Fill', '#A7DEE6', 2),
(545, 145, 'Outline', '#366395', 0),
(546, 145, 'Fill 1', '#4886CE', 1),
(547, 146, 'Gradient Top', '#2B4E99', 0),
(548, 146, 'Gradient Middle', '#6E9FFC', 1),
(549, 146, 'Gradient Bottom', '#B0EBFF', 2),
(552, 147, 'Balloon Fill 1', '#EB5095', 0),
(553, 147, 'Balloon Fill 2', '#F36AAB', 1),
(554, 145, 'Fill 2', '#62A8E1', 2),
(555, 147, 'Balloon Fill 3', '#F88FC2', 2),
(556, 147, 'Balloon Fill 4', '#FED3E9', 3),
(557, 147, 'Confetti 1', '#7F6DAF', 4),
(558, 147, 'Confetti 2', '#FDAE75', 5),
(559, 147, 'Confetti 3', '#FCD269', 6),
(560, 147, 'Confetti 4', '#FFFFFF', 7),
(613, 164, 'Outline', '#D1A2E8', 0),
(614, 164, 'Fill', '#FCCFFF', 1),
(616, 164, 'Shadow Fill', '#EEC0F8', 2),
(617, 165, 'Outline', '#683A8A', 0),
(618, 165, 'Fill 1', '#7B45A4', 1),
(619, 166, 'Gradient Top', '#362C91', 0),
(621, 166, 'Gradient Bottom', '#A0A6FD', 1),
(622, 166, 'Highlight Top', '#9996FB', 2),
(623, 166, 'Highlight Bottom', '#C5C4FD', 3),
(624, 167, 'Star Fill 1', '#CB79CD', 0),
(625, 167, 'Star Fill 2', '#FDFDF2', 1),
(626, 165, 'Fill 2', '#9952C9', 2),
(627, 165, 'Fill 3', '#A4EDE1', 3),
(628, 167, 'Trail Fill 1', '#19C8C7', 2),
(629, 167, 'Trail Fill 2', '#A4F1E2', 3),
(630, 168, 'Magic', '#70DACC', 0),
(631, 169, 'Outline', '#E9CDA4', 0),
(632, 169, 'Fill', '#FBF4DF', 1),
(633, 169, 'Shadow Outline', '#DEC098', 2),
(634, 169, 'Shadow Fill', '#EFE2CA', 3),
(635, 170, 'Outline', '#45C8D5', 0),
(636, 170, 'Fill 1', '#96E5EF', 1),
(637, 171, 'Gradient Top', '#26A9BF', 0),
(639, 171, 'Gradient Bottom', '#D7FFFA', 1),
(640, 171, 'Highlight Top', '#C3F4FD', 2),
(641, 171, 'Highlight Bottom', '#EFFCFC', 3),
(642, 172, 'Hat Fill 1', '#D39CD1', 0),
(643, 172, 'Hat Fill 2', '#B674BC', 1),
(644, 170, 'Fill 2', '#CCF9F9', 2),
(645, 173, 'Collar Outline', '#985C9D', 0),
(646, 173, 'Collar Fill 1', '#D39CD1', 1),
(647, 173, 'Collar Fill 2', '#FBFBFB', 2),
(648, 173, 'Collar Shadow Fill', '#B674BC', 3),
(649, 173, 'Tie Outline', '#E64E07', 4),
(650, 173, 'Tie Fill', '#FF7D74', 5),
(651, 174, 'Main Outline', '#E64E07', 0),
(652, 174, 'Main Fill 1', '#FF7D74', 1),
(653, 174, 'Main Fill 2', '#FFA085', 2),
(654, 174, 'Blossom Outline', '#6C3F6B', 3),
(655, 174, 'Blossom Outside Fill', '#985C9D', 4),
(656, 174, 'Blossom Inside Fill 1', '#955E9C', 5),
(657, 174, 'Blossom Inside Fill 2', '#D39CD1', 6),
(658, 172, 'Hat Fill 3', '#FBFBFB', 2),
(659, 172, 'Plume Fill', '#FF5555', 3),
(660, 172, 'Plume Shadow', '#F24C4C', 4),
(661, 175, 'Outline', '#151730', 0),
(662, 175, 'Fill', '#38407C', 1),
(663, 175, 'Shadow Fill', '#30376C', 2),
(664, 176, 'Outline', '#8952E1', 0),
(665, 176, 'Edge Fill (50% opacity)', '#473CC7', 1),
(666, 176, 'Main Fill', '#1C4FC2', 2),
(667, 177, 'Outline', '#9791F1', 0),
(668, 177, 'Fill', '#C4BFF9', 1),
(669, 177, 'Shadow Outline', '#7E79CB', 2),
(670, 177, 'Shadow Fill', '#9F9DCE', 3),
(671, 178, 'Outline', '#9F9DCE', 0),
(672, 178, 'Fill', '#000000', 1),
(673, 178, 'Moon', '#FFFFFF', 2),
(674, 179, 'Dark Side of the Moon', '#000000', 0),
(675, 179, 'Moon', '#FFFFFF', 1),
(676, 176, 'Stars', '#FFFFFF', 3),
(677, 180, 'Gradient Top', '#0E6183', 0),
(678, 180, 'Gradient Bottom', '#06A79F', 1),
(679, 180, 'Highlight Top', '#61E9D1', 2),
(680, 180, 'Highlight Bottom', '#9AFCFD', 3),
(681, 180, 'Eyeshadow', '#7696ED', 4),
(683, 182, 'Outline', '#FA9EDE', 0),
(684, 182, 'Fill', '#FFD9FB', 1),
(685, 182, 'Shadow Outline', '#F591D7', 2),
(686, 182, 'Shadow Fill', '#FDBBEC', 3),
(687, 183, 'Outline', '#6B5584', 0),
(688, 183, 'Fill', '#9775B8', 1),
(689, 184, 'Gradient Top', '#584239', 0),
(690, 184, 'Gradient Middle', '#A87A66', 1),
(691, 184, 'Gradient Bottom', '#DFB77D', 2),
(692, 184, 'Highlight Top', '#CEA06F', 3),
(693, 184, 'Highlight Bottom', '#E2C6AB', 4),
(694, 185, 'Button 1 main', '#9776B8', 0),
(695, 185, 'Button 1 holes', '#43324C', 1),
(696, 186, 'Outline', '#5EC3EF', 0),
(697, 186, 'Main fill', '#C3F6FF', 1),
(698, 186, 'Stripes', '#7CD7F9', 2),
(699, 186, 'Dots', '#FBFBFB', 3),
(700, 187, 'Outline', '#F6DDA5', 0),
(701, 187, 'Fill', '#FFF9D8', 1),
(702, 185, 'Button 1 rim', '#72528C', 2),
(703, 185, 'Button 2 main', '#FFF8D8', 3),
(704, 185, 'Button 2 holes', '#8E734D', 4),
(705, 185, 'Button 2 rim', '#EFDEAD', 5),
(706, 185, 'Button 3 main', '#C4F5FF', 6),
(707, 185, 'Button 3 holes', '#2F6E87', 7),
(708, 185, 'Button 3 rim', '#6ABFE3', 8),
(709, 188, 'Aura', '#D9539D', 0),
(710, 189, 'Aura', '#E8DA9C', 0),
(711, 190, 'Aura', '#6BF0ED', 0);

CREATE TABLE `ponies` (
  `id` int(11) NOT NULL,
  `order` int(11) DEFAULT NULL,
  `label` tinytext NOT NULL,
  `notes` tinytext NOT NULL,
  `cm_favme` varchar(20) DEFAULT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `ponies` (`id`, `order`, `label`, `notes`, `cm_favme`, `added`) VALUES
(1, 1, 'Twilight Sparkle', 'Far legs use darker colors.', 'd64bqyo', '2015-08-24 19:04:32'),
(2, 2, 'Applejack', '', 'd64bqyo', '2015-09-21 18:46:54'),
(3, 3, 'Fluttershy', '', 'd64bqyo', '2015-09-21 18:54:47'),
(4, 4, 'Pinkie Pie', 'Far legs use darker colors.', 'd64bqyo', '2015-09-21 18:52:26'),
(5, 5, 'Rainbow Dash', '', 'd64bqyo', '2015-09-21 19:01:33'),
(6, 6, 'Rarity', '', 'd64bqyo', '2015-09-21 19:18:24'),
(7, 7, 'Spike', '', NULL, '2015-09-21 19:43:59'),
(9, 13, 'Princess Luna', '', NULL, '2015-09-29 18:55:38'),
(10, NULL, 'Minuette', 'For convenience, the color of the glass on her cutie mark is solid rather than transparent, thus the sand should be above.', NULL, '2015-07-25 14:49:44'),
(11, NULL, 'Derpy / Muffins', 'From S509 - Slice of Life', NULL, '2015-08-26 03:53:49'),
(12, NULL, 'Lyra Heartstrings', 'From S5E09 - Slice of Life', NULL, '2015-08-26 04:08:33'),
(13, NULL, 'Whoa Nelly', '', NULL, '2015-09-17 03:43:22'),
(14, NULL, 'Fashion Plate', '', NULL, '2015-09-17 04:02:26'),
(15, NULL, 'Sassy Saddles', '', NULL, '2015-09-17 04:22:20'),
(16, NULL, 'Twinkleshine', '', NULL, '2015-09-21 20:25:31'),
(17, NULL, 'Lemon Hearts', '', NULL, '2015-09-21 20:30:06'),
(18, NULL, 'Granny Smith', 'Far legs use darker colors.', NULL, '2015-09-21 20:37:57'),
(19, NULL, 'Fleetfoot', '', NULL, '2015-09-21 20:58:56'),
(20, NULL, 'Stormy Flare', 'Only has one eye shine.', NULL, '2015-09-22 05:20:14'),
(21, NULL, 'Wind Rider', 'Teeth use a different color than normal.', NULL, '2015-09-24 04:35:32'),
(22, NULL, 'Sugar Belle', '', NULL, '2015-09-24 10:44:18'),
(23, 9, 'Apple Bloom', 'Far legs use darker colors.', NULL, '2015-09-24 12:01:03'),
(24, 10, 'Scootaloo', 'Far legs use darker colors.', NULL, '2015-09-24 12:06:23'),
(25, 11, 'Sweetie Belle', '', NULL, '2015-09-24 12:09:41'),
(26, NULL, 'Night Glider', 'From S5e02', NULL, '2015-09-26 15:03:43'),
(27, NULL, 'Double Diamond', '', NULL, '2015-09-26 17:22:19'),
(28, NULL, 'Party Favor', 'Magic aura color is unknown.', NULL, '2015-09-26 17:34:42'),
(29, NULL, 'Starlight Glimmer', '', NULL, '2015-09-26 18:01:51'),
(30, NULL, 'Coco Pommel', 'From S4E08', NULL, '2015-09-26 21:18:32'),
(31, NULL, 'Suri Polomare', 'From S4E08', NULL, '2015-09-30 05:35:59');

CREATE TABLE `tagged` (
  `tid` int(11) NOT NULL,
  `ponyid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tagged` (`tid`, `ponyid`) VALUES
(4, 1),
(6, 1),
(12, 1),
(14, 1),
(2, 2),
(6, 2),
(12, 2),
(22, 2),
(3, 3),
(6, 3),
(12, 3),
(24, 3),
(2, 4),
(6, 4),
(12, 4),
(23, 4),
(3, 5),
(6, 5),
(12, 5),
(25, 5),
(1, 6),
(6, 6),
(12, 6),
(26, 6),
(6, 7),
(11, 7),
(29, 7),
(30, 7),
(4, 9),
(7, 9),
(12, 9),
(57, 9),
(59, 9),
(1, 10),
(7, 10),
(12, 10),
(31, 10),
(3, 11),
(7, 11),
(12, 11),
(50, 11),
(1, 12),
(7, 12),
(12, 12),
(32, 12),
(1, 13),
(8, 13),
(12, 13),
(43, 13),
(51, 13),
(1, 14),
(7, 14),
(11, 14),
(33, 14),
(43, 14),
(1, 15),
(7, 15),
(12, 15),
(34, 15),
(1, 16),
(7, 16),
(12, 16),
(35, 16),
(1, 17),
(7, 17),
(12, 17),
(36, 17),
(2, 18),
(7, 18),
(12, 18),
(28, 18),
(37, 18),
(3, 19),
(7, 19),
(12, 19),
(27, 19),
(38, 19),
(3, 20),
(7, 20),
(12, 20),
(28, 20),
(39, 20),
(42, 20),
(3, 21),
(7, 21),
(11, 21),
(27, 21),
(40, 21),
(42, 21),
(1, 22),
(7, 22),
(12, 22),
(41, 22),
(2, 23),
(7, 23),
(12, 23),
(44, 23),
(45, 23),
(46, 23),
(3, 24),
(7, 24),
(12, 24),
(44, 24),
(45, 24),
(47, 24),
(1, 25),
(7, 25),
(12, 25),
(44, 25),
(45, 25),
(48, 25),
(3, 26),
(7, 26),
(12, 26),
(49, 26),
(2, 27),
(7, 27),
(11, 27),
(52, 27),
(1, 28),
(7, 28),
(11, 28),
(53, 28),
(1, 29),
(9, 29),
(12, 29),
(54, 29),
(2, 30),
(7, 30),
(12, 30),
(55, 30),
(56, 30),
(2, 31),
(9, 31),
(12, 31),
(58, 31);

CREATE TABLE `tags` (
  `tid` int(11) NOT NULL,
  `name` varchar(25) NOT NULL,
  `title` tinytext NOT NULL,
  `type` enum('spec','gen','cat','app','ep','char') DEFAULT NULL,
  `uses` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tags` (`tid`, `name`, `title`, `type`, `uses`) VALUES
(1, 'unicorn', '', 'spec', 12),
(2, 'earth pony', '', 'spec', 7),
(3, 'pegasus', '', 'spec', 8),
(4, 'alicorn', '', 'spec', 2),
(5, 'bat pony', '', 'spec', 0),
(6, 'mane six', 'Ponies who are one of the show''s six main characters', 'cat', 7),
(7, 'minor character', 'Ponies who had a speaking role and/or interacted with the mane six', 'cat', 20),
(8, 'background character', 'Ponies whose only purpose is filling crowds, with no to minimal speaking roles', 'cat', 1),
(9, 'antagonist', '', 'cat', 2),
(10, 'pet', '', 'cat', 0),
(11, 'male', '', 'gen', 5),
(12, 'female', '', 'gen', 25),
(14, 'twilight sparkle', '', 'char', 1),
(15, 'gala dress', 'All gala dress colors', 'app', 0),
(16, 'human', 'Refers to Equestria Girls characters', 'spec', 0),
(19, 's1e1', '', 'ep', 0),
(20, 's1e26', '', 'ep', 0),
(21, 's5e12', '', 'ep', 0),
(22, 'applejack', '', 'char', 1),
(23, 'pinkie pie', '', 'char', 1),
(24, 'fluttershy', '', 'char', 1),
(25, 'rainbow dash', '', 'char', 1),
(26, 'rarity', '', 'char', 1),
(27, 'wonderbolt', 'Wonderbolt characters', 'cat', 2),
(28, 'parent', 'Parents of other characters', 'cat', 2),
(29, 'dragon', '', 'spec', 1),
(30, 'spike', '', 'char', 1),
(31, 'minuette', '', 'char', 1),
(32, 'lyra heartstrings', '', 'char', 1),
(33, 'fashion plate', '', 'char', 1),
(34, 'sassy saddles', '', 'char', 1),
(35, 'twinkleshine', '', 'char', 1),
(36, 'lemon hearts', '', 'char', 1),
(37, 'granny smith', '', 'char', 1),
(38, 'fleetfoot', '', 'char', 1),
(39, 'stormy flare', '', 'char', 1),
(40, 'wind rider', '', 'char', 1),
(41, 'sugar belle', '', 'char', 1),
(42, 's5e15', '', 'ep', 2),
(43, 's5e14', '', 'ep', 2),
(44, 'foal', '', 'cat', 3),
(45, 'cutie mark crusader', '', 'cat', 3),
(46, 'apple bloom', '', 'char', 1),
(47, 'scootaloo', '', 'char', 1),
(48, 'sweetie belle', '', 'char', 1),
(49, 'night glider', '', 'char', 1),
(50, 'derpy hooves', 'Derpy Hooves or Muffins', 'char', 1),
(51, 'whoa nelly', '', 'char', 1),
(52, 'double diamond', '', 'char', 1),
(53, 'party favor', '', 'char', 1),
(54, 'starlight glimmer', '', 'char', 1),
(55, 'coco pommel', '', 'char', 1),
(56, 'manehatten', '', 'cat', 1),
(57, 'princess luna', '', 'char', 1),
(58, 'suri polomare', '', 'char', 1),
(59, 'royalty', '', 'cat', 1);


ALTER TABLE `colorgroups`
  ADD PRIMARY KEY (`groupid`),
  ADD UNIQUE KEY `groupid` (`groupid`,`ponyid`,`label`),
  ADD KEY `ponyid` (`ponyid`);

ALTER TABLE `colors`
  ADD PRIMARY KEY (`colorid`),
  ADD KEY `groupid` (`groupid`);

ALTER TABLE `ponies`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tagged`
  ADD PRIMARY KEY (`tid`,`ponyid`),
  ADD KEY `ponyid` (`ponyid`);

ALTER TABLE `tags`
  ADD PRIMARY KEY (`tid`);


ALTER TABLE `colorgroups`
  MODIFY `groupid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;
ALTER TABLE `colors`
  MODIFY `colorid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=712;
ALTER TABLE `ponies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;
ALTER TABLE `tags`
  MODIFY `tid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

ALTER TABLE `colorgroups`
  ADD CONSTRAINT `colorgroups_ibfk_1` FOREIGN KEY (`ponyid`) REFERENCES `ponies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `colors`
  ADD CONSTRAINT `colors_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `colorgroups` (`groupid`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tagged`
  ADD CONSTRAINT `tagged_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `tags` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tagged_ibfk_2` FOREIGN KEY (`ponyid`) REFERENCES `ponies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
