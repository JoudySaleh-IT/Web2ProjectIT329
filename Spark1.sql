-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 16, 2025 at 10:23 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Spark1`
--

-- --------------------------------------------------------

--
-- Table structure for table `Quiz`
--

CREATE TABLE `Quiz` (
  `id` int NOT NULL,
  `educatorID` int NOT NULL,
  `topicID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Quiz`
--

INSERT INTO `Quiz` (`id`, `educatorID`, `topicID`) VALUES
(11, 31, 1),
(12, 31, 3),
(13, 33, 4),
(14, 35, 3),
(15, 35, 4),
(16, 36, 1),
(17, 36, 3);

-- --------------------------------------------------------

--
-- Table structure for table `QuizFeedback`
--

CREATE TABLE `QuizFeedback` (
  `id` int NOT NULL,
  `quizID` int NOT NULL,
  `rating` int NOT NULL,
  `comments` text,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `QuizFeedback`
--

INSERT INTO `QuizFeedback` (`id`, `quizID`, `rating`, `comments`, `date`) VALUES
(7, 11, 4, 'so hard', '2025-11-08 01:16:56'),
(8, 12, 5, 'Best quiz ever', '2025-11-08 01:25:05'),
(9, 11, 2, 'Hard', '2025-11-08 01:25:26'),
(10, 12, 4, 'thank god', '2025-11-09 15:01:25'),
(11, 11, 4, 'hello this is norah alkathiri comment', '2025-11-09 15:03:55'),
(12, 11, 3, 'hgg', '2025-11-12 10:11:41');

-- --------------------------------------------------------

--
-- Table structure for table `QuizQuestion`
--

CREATE TABLE `QuizQuestion` (
  `id` int NOT NULL,
  `quizID` int NOT NULL,
  `question` text NOT NULL,
  `questionFigureFileName` varchar(255) DEFAULT NULL,
  `answerA` varchar(255) NOT NULL,
  `answerB` varchar(255) NOT NULL,
  `answerC` varchar(255) NOT NULL,
  `answerD` varchar(255) NOT NULL,
  `correctAnswer` enum('A','B','C','D') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `QuizQuestion`
--

INSERT INTO `QuizQuestion` (`id`, `quizID`, `question`, `questionFigureFileName`, `answerA`, `answerB`, `answerC`, `answerD`, `correctAnswer`) VALUES
(14, 12, 'Which word means “to make something better”?', NULL, 'Improve', 'Remove', 'Approve', 'Move', 'A'),
(15, 11, 'What is 25% of 80?', NULL, '15', '18', '20', '25', 'C'),
(16, 11, 'If 5 pencils cost 15 SAR, how much do 8 pencils cost?', NULL, '24', '22', '25', '28', 'A'),
(17, 11, 'Convert 0.75 to a fraction.', NULL, '1/2', '2/3', '3/4', '4/5', 'C'),
(18, 11, '9^2 - 5^2 = ?', NULL, '64', '56', '76', '84', 'B'),
(19, 11, 'If a shirt costs 80 SAR after a 20% discount, what was the original price?', NULL, '90', '120', '96', '100', 'D'),
(20, 11, 'If a = 3 and b = 4, find a^2 + b^2:', NULL, '7', '12', '25', '30', 'C'),
(21, 13, 'In which year did World War II end?', NULL, '1945', '1939', '1940', '1950', 'A'),
(22, 13, 'What is the name of the vision launched to diversify Saudi Arabia’s economy?', NULL, 'Future Vision', 'Vision 2030', 'Vision 2020', 'National Plan', 'B'),
(23, 13, 'Where did King Abdulaziz first capture to begin unifying Saudi Arabia in 1902?', NULL, 'Mecca', 'Diriyah', 'Riyadh', 'Qassim', 'C'),
(24, 12, 'What does the idiom “break the ice” mean?', NULL, 'To start a conversation in a friendly way', 'To break something frozen', 'To stop talking to someone', 'To get angry quickly', 'A');

-- --------------------------------------------------------

--
-- Table structure for table `RecommendedQuestion`
--

CREATE TABLE `RecommendedQuestion` (
  `id` int NOT NULL,
  `quizID` int NOT NULL,
  `learnerID` int NOT NULL,
  `question` text NOT NULL,
  `questionFigureFileName` varchar(255) DEFAULT NULL,
  `answerA` varchar(255) NOT NULL,
  `answerB` varchar(255) NOT NULL,
  `answerC` varchar(255) NOT NULL,
  `answerD` varchar(255) NOT NULL,
  `correctAnswer` enum('A','B','C','D') NOT NULL,
  `status` enum('pending','approved','disapproved') NOT NULL DEFAULT 'pending',
  `comments` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `RecommendedQuestion`
--

INSERT INTO `RecommendedQuestion` (`id`, `quizID`, `learnerID`, `question`, `questionFigureFileName`, `answerA`, `answerB`, `answerC`, `answerD`, `correctAnswer`, `status`, `comments`) VALUES
(6, 13, 30, 'Where did King Abdulaziz first capture to begin unifying Saudi Arabia in 1902?', NULL, 'Mecca', 'Diriyah', 'Riyadh', 'Qassim', 'C', 'approved', 'Good Question'),
(7, 11, 30, '1+1', NULL, '2', '4', '6', '10', 'A', 'disapproved', ''),
(8, 12, 30, 'What does the idiom “break the ice” mean?', NULL, 'To start a conversation in a friendly way', 'To break something frozen', 'To stop talking to someone', 'To get angry quickly', 'A', 'approved', 'Great!'),
(9, 13, 30, 'Hello my name is norah alkathiri please don\'t forget me', NULL, 'meow', 'hello', 'bye', 'station', 'A', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `TakenQuiz`
--

CREATE TABLE `TakenQuiz` (
  `id` int NOT NULL,
  `quizID` int NOT NULL,
  `score` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `TakenQuiz`
--

INSERT INTO `TakenQuiz` (`id`, `quizID`, `score`) VALUES
(10, 11, 20),
(11, 12, 100),
(12, 11, 40),
(13, 11, 40),
(14, 12, 100),
(15, 11, 60),
(16, 11, 0);

-- --------------------------------------------------------

--
-- Table structure for table `Topic`
--

CREATE TABLE `Topic` (
  `id` int NOT NULL,
  `topicName` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Topic`
--

INSERT INTO `Topic` (`id`, `topicName`) VALUES
(3, 'English'),
(4, 'History'),
(1, 'Math');

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE `User` (
  `id` int NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photoFileName` varchar(255) DEFAULT NULL,
  `userType` enum('learner','educator') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `User`
--

INSERT INTO `User` (`id`, `firstName`, `lastName`, `emailAddress`, `password`, `photoFileName`, `userType`) VALUES
(2, 'Faisal', 'Alharbi', 'faisal@example.com', '$2y$10$L0gGInQ2h0bO1PjJx6qvUO3R9m1pGFM7pU9b7nJ0T6xv4o3QnCkN2', NULL, 'learner'),
(3, 'Sara', 'Almutairi', 'sara@example.com', '$2y$10$L0gGInQ2h0bO1PjJx6qvUO3R9m1pGFM7pU9b7nJ0T6xv4o3QnCkN2', NULL, 'learner'),
(30, 'walah', 'alsaeed', 'walah1@gmail.com', '$2y$10$Eqia4qwhAmFj1houTj1VCONj.y39ImrHC7JU8nCRBi3xndluiJYJe', 'usr_1762552922.png', 'learner'),
(31, 'Hessah', 'Alsaaran', 'hessah1@gmail.com', '$2y$10$MzZFQVLyZtS2Ptnaif8Y6OiHjZEozBy90d9jyVVYHr6tPdV8s3mUu', 'usr_1762553021.png', 'educator'),
(32, 'Joud', 'Alzahrani', 'joudsaleh@gmail.com', '$2y$10$jo1/z8qIVmRvlWZfHCVnNOarnv7PmdUtPiXUGIdF9odKhZRY4NVP2', 'Defaultavatar.jpg', 'learner'),
(33, 'Kholoud', 'Alqhtani', 'Kholoudksu@gmail.com', '$2y$10$rHQ0WIH.A6wEjiQrm6.G6uY8ajGDYDGRyG9/DZamNLRCiqEWYzrlS', 'Defaultavatar.jpg', 'educator'),
(34, 'Joud', 'Alzahrani', 'joud@gmail.com', '$2y$10$IXxbls8qHNiN6mB0boQPTOeTqa9BKEup5uXgxFmIbc3fvHA7lf8M.', 'Defaultavatar.jpg', 'learner'),
(35, 'Norah', 'Alkathiri', 'norahk@gmail.com', '$2y$10$10ubEbATam7MFcmT5laB8O3bF1i2cZ4dhBY/5CTk9nwG2eS0n2AWu', 'Defaultavatar.jpg', 'educator'),
(36, 'noura', '2', 'noura12@gmail.com', '$2y$10$/6n8PTnfPZHv5kUyV4zHX.ZqdZ4vs3gyM52PM0Fv72oK1mv0s3b6e', 'usr_1762930772.jpg', 'educator');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Quiz`
--
ALTER TABLE `Quiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quiz_user` (`educatorID`),
  ADD KEY `fk_quiz_topic` (`topicID`);

--
-- Indexes for table `QuizFeedback`
--
ALTER TABLE `QuizFeedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feedback_quiz` (`quizID`);

--
-- Indexes for table `QuizQuestion`
--
ALTER TABLE `QuizQuestion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_question_quiz` (`quizID`);

--
-- Indexes for table `RecommendedQuestion`
--
ALTER TABLE `RecommendedQuestion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recommended_quiz` (`quizID`),
  ADD KEY `fk_recommended_learner` (`learnerID`);

--
-- Indexes for table `TakenQuiz`
--
ALTER TABLE `TakenQuiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_taken_quiz` (`quizID`);

--
-- Indexes for table `Topic`
--
ALTER TABLE `Topic`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `topicName` (`topicName`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emailAddress` (`emailAddress`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Quiz`
--
ALTER TABLE `Quiz`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `QuizFeedback`
--
ALTER TABLE `QuizFeedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `QuizQuestion`
--
ALTER TABLE `QuizQuestion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `RecommendedQuestion`
--
ALTER TABLE `RecommendedQuestion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `TakenQuiz`
--
ALTER TABLE `TakenQuiz`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `Topic`
--
ALTER TABLE `Topic`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `User`
--
ALTER TABLE `User`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Quiz`
--
ALTER TABLE `Quiz`
  ADD CONSTRAINT `fk_quiz_topic` FOREIGN KEY (`topicID`) REFERENCES `Topic` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_quiz_user` FOREIGN KEY (`educatorID`) REFERENCES `User` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `QuizFeedback`
--
ALTER TABLE `QuizFeedback`
  ADD CONSTRAINT `fk_feedback_quiz` FOREIGN KEY (`quizID`) REFERENCES `Quiz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `QuizQuestion`
--
ALTER TABLE `QuizQuestion`
  ADD CONSTRAINT `fk_question_quiz` FOREIGN KEY (`quizID`) REFERENCES `Quiz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `RecommendedQuestion`
--
ALTER TABLE `RecommendedQuestion`
  ADD CONSTRAINT `fk_recommended_learner` FOREIGN KEY (`learnerID`) REFERENCES `User` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recommended_quiz` FOREIGN KEY (`quizID`) REFERENCES `Quiz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `TakenQuiz`
--
ALTER TABLE `TakenQuiz`
  ADD CONSTRAINT `fk_taken_quiz` FOREIGN KEY (`quizID`) REFERENCES `Quiz` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
