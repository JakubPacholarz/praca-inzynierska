-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 21, 2025 at 09:39 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `baza_aplikacja`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `actions`
--

CREATE TABLE `actions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `profit_loss` decimal(10,2) NOT NULL,
  `date_bought` date NOT NULL,
  `date_sold` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `actions`
--

INSERT INTO `actions` (`id`, `user_id`, `type`, `amount`, `profit_loss`, `date_bought`, `date_sold`) VALUES
(1, 1, 'Crypto-Bitcoin', 500.00, 1.57, '2025-02-13', '2025-02-13'),
(2, 3, 'Crypto-Bitcoin', 1000.00, 0.00, '2025-02-14', '2025-02-14'),
(3, 4, 'Crypto-Ethereum', 3000.00, -25.58, '2025-02-21', '2025-02-21'),
(4, 4, 'Crypto-Dogecoin', 10000.00, 140.80, '2025-02-21', '2025-02-21'),
(5, 4, 'Crypto-Litecoin', 15000.00, 13.54, '2025-02-21', '2025-02-21'),
(6, 4, 'Crypto-Polkadot', 10000.00, -19.19, '2025-02-21', '2025-02-21'),
(7, 4, 'Crypto-Dogecoin', 10000.00, -10000.00, '2025-02-21', '2025-02-21'),
(8, 4, 'Crypto-Ripple', 20000.00, -20000.00, '2025-02-21', '2025-02-21'),
(9, 5, 'Crypto-Dogecoin', 50000.00, 393.60, '2025-02-21', '2025-02-21'),
(10, 5, 'Crypto-Dogecoin', 25000.00, 174.20, '2025-02-21', '2025-02-21'),
(11, 5, 'Crypto-Dogecoin', 25000.00, 156.40, '2025-02-21', '2025-02-21'),
(12, 5, 'Crypto-Dogecoin', 30000.00, 491.88, '2025-02-21', '2025-02-21'),
(13, 5, 'Crypto-Dogecoin', 50000.00, 861.80, '2025-02-21', '2025-02-21');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `income`
--

CREATE TABLE `income` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('Regular','Irregular') NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `income`
--

INSERT INTO `income` (`id`, `user_id`, `type`, `description`, `amount`, `date`) VALUES
(1, 1, 'Irregular', 'Gift', 1000.00, '2025-02-11'),
(2, 1, 'Irregular', 'Money', 1000.00, '2025-02-01'),
(3, 1, 'Irregular', '', 1.57, '2025-02-13'),
(4, 1, 'Regular', 'Form daddy ', 1000.00, '2025-02-03'),
(5, 3, 'Regular', 'Word', 10000.00, '2025-02-06'),
(6, 3, 'Irregular', 'Gift', 500.00, '2025-02-01'),
(7, 3, 'Regular', 'Paychek', 5000.00, '2025-02-12'),
(8, 3, 'Irregular', '', 0.00, '2025-02-14'),
(9, 3, 'Irregular', 'Paycheck', 450.00, '2025-02-06'),
(10, 3, 'Regular', 'Paychek', 100.00, '2025-02-05'),
(11, 4, 'Irregular', 'aaa', 10000.00, '2025-02-20'),
(12, 4, 'Regular', '1111', 10000.00, '2025-02-05'),
(13, 4, 'Irregular', '', -25.58, '2025-02-21'),
(14, 4, 'Irregular', '', 140.80, '2025-02-21'),
(15, 4, 'Irregular', '', 13.54, '2025-02-21'),
(16, 4, 'Irregular', 'qasd', 10000.00, '2025-02-21'),
(17, 4, 'Irregular', '', -19.19, '2025-02-21'),
(18, 4, 'Irregular', '', -10000.00, '2025-02-21'),
(19, 4, 'Irregular', '', -20000.00, '2025-02-21'),
(20, 5, 'Irregular', '123', 100000.00, '2025-02-21'),
(21, 5, 'Irregular', '', 393.60, '2025-02-21'),
(22, 5, 'Irregular', '', 174.20, '2025-02-21'),
(23, 5, 'Irregular', '', 156.40, '2025-02-21'),
(24, 5, 'Irregular', '', 491.88, '2025-02-21'),
(25, 5, 'Irregular', '', 861.80, '2025-02-21');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `investments`
--

CREATE TABLE `investments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `price_at_investment` decimal(10,2) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `investments`
--

INSERT INTO `investments` (`id`, `user_id`, `type`, `amount`, `price_at_investment`, `date`) VALUES
(2, 1, 'Crypto-Bitcoin', 900.00, 95555.00, '2025-02-13'),
(3, 3, 'Crypto-Bitcoin', 2500.00, 95912.00, '2025-02-13');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `irregular_payments`
--

CREATE TABLE `irregular_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `amount`, `description`, `category`, `date`) VALUES
(1, 1, 100.00, 'Fun', 'Entertainment', '2025-02-03'),
(2, 1, 100.00, 'Present', 'Other', '2025-02-01'),
(3, 1, 500.00, 'Gas', 'Transportation', '2024-10-25'),
(4, 3, 100.00, 'Fun', 'Entertainment', '2025-02-08'),
(5, 3, 50.00, 'BEER WITH FRIENDS', 'Entertainment', '2025-02-09'),
(6, 3, 100.00, 'New Shoes', 'Clothing', '2025-02-13'),
(7, 3, 450.00, 'Gas', 'Transportation', '2025-02-12'),
(8, 3, 150.00, 'Pizza With family', 'Dining Out', '2025-01-30'),
(9, 3, 500.00, 'Party', 'Entertainment', '2025-01-01'),
(10, 3, 500.00, 'Food', 'Groceries', '2025-02-08'),
(11, 3, 100.00, 'Rent', 'Entertainment', '2025-02-13'),
(12, 3, 900.00, 'Rent', 'Entertainment', '2025-02-12'),
(13, 3, 450.00, 'Rent', 'Entertainment', '2025-02-13'),
(14, 3, 200.00, 'New pants', 'Clothing', '2025-02-11');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `regular_payments`
--

CREATE TABLE `regular_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `regular_payments`
--

INSERT INTO `regular_payments` (`id`, `user_id`, `amount`, `description`, `date`) VALUES
(1, 1, 900.00, 'Rent', '2025-02-13'),
(2, 3, 2000.00, 'Rent', '2025-02-10'),
(3, 4, 2500.00, 'Rent', '2025-02-06');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT 0.00,
  `share_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `photo`, `budget`, `share_code`, `created_at`) VALUES
(1, 'popo', '', '$2y$10$9xAYe2P3J9pp1RPyvfjrm.M.kKjrF.N5MgY719XBNKyUGRsMDd0MC', 'uploads/ZDJĘCIE PROFILOWE.png', 2100.00, '2a5fb322d916bbd3e176b64e027b27e5', '2025-02-13 16:42:33'),
(2, 'aaa', '', '$2y$10$GRqK3Yy/ncMKjPeUTJzyhOaLEBL5OArTaFiMETdagN8x3L4Ope0/y', NULL, 0.00, '48e1e6d76bf08060adfbf4d0b8d77ad4', '2025-02-13 17:38:22'),
(3, 'PREZENTACJA', '', '$2y$10$Ein0i2HHWaKGdtFZrthK2OVZXnGwGTq5LkqmH61zXDMGE4GupYQ6e', 'uploads/ZDJĘCIE PROFILOWE.png', 16050.00, '8793064fbddea708011a73a658ae58fe', '2025-02-13 17:43:10'),
(4, 'pokazanie', '', '$2y$10$vSZSkIRrK7t3sio5pVsoj.ANrE9U.aLwJf9edoURaqu1bWBBVXKoi', NULL, 30000.00, '46d198f8bdb606f481d9e126d885801c', '2025-02-21 15:38:08'),
(5, '123', '', '$2y$10$lLCBHr9bmJybJpXe9I40DeU.MkVOLwXxmxTTjL7hEt.9sjXELZAnG', NULL, 100000.00, 'c250dc3b43275c1b9b90b1cd6f7e5e8e', '2025-02-21 16:06:20');

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `actions`
--
ALTER TABLE `actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `income`
--
ALTER TABLE `income`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `investments`
--
ALTER TABLE `investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `irregular_payments`
--
ALTER TABLE `irregular_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `regular_payments`
--
ALTER TABLE `regular_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `actions`
--
ALTER TABLE `actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `income`
--
ALTER TABLE `income`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `investments`
--
ALTER TABLE `investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `irregular_payments`
--
ALTER TABLE `irregular_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `regular_payments`
--
ALTER TABLE `regular_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `actions`
--
ALTER TABLE `actions`
  ADD CONSTRAINT `actions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `income`
--
ALTER TABLE `income`
  ADD CONSTRAINT `income_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `investments`
--
ALTER TABLE `investments`
  ADD CONSTRAINT `investments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `irregular_payments`
--
ALTER TABLE `irregular_payments`
  ADD CONSTRAINT `irregular_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `regular_payments`
--
ALTER TABLE `regular_payments`
  ADD CONSTRAINT `regular_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
