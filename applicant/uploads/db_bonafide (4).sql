-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 11, 2025 at 01:26 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_bonafide`
--

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `application_id` int(11) NOT NULL,
  `job_post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `resume_reference` varchar(255) DEFAULT NULL,
  `work_experience` int(11) NOT NULL,
  `status` enum('Shortlisted','Rejected','Pending','Screened','Interviewed','Offered','Hired','Withdrawn') DEFAULT 'Pending',
  `comments` text NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `screened_at` timestamp NULL DEFAULT NULL,
  `interviewed_at` timestamp NULL DEFAULT NULL,
  `offered_at` timestamp NULL DEFAULT NULL,
  `deployed_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `duration_applied_to_screened` int(11) DEFAULT NULL,
  `duration_screened_to_interviewed` int(11) DEFAULT NULL,
  `duration_interviewed_to_offered` int(11) DEFAULT NULL,
  `duration_offered_to_hired` int(11) DEFAULT NULL,
  `total_duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`application_id`, `job_post_id`, `user_id`, `resume_reference`, `work_experience`, `status`, `comments`, `applied_at`, `screened_at`, `interviewed_at`, `offered_at`, `deployed_at`, `rejected_at`, `withdrawn_at`, `duration_applied_to_screened`, `duration_screened_to_interviewed`, `duration_interviewed_to_offered`, `duration_offered_to_hired`, `total_duration`) VALUES
(58, 23, 17, 'Ramos - ITP110 - Finals Activity 1.pdf', 20, 'Shortlisted', '', '2025-01-11 06:57:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(59, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Pending', '', '2025-01-11 10:32:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(60, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Shortlisted', '', '2025-01-11 10:36:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(61, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Shortlisted', '', '2025-01-11 10:41:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(62, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Shortlisted', '', '2025-01-11 10:41:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(63, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Shortlisted', '', '2025-01-11 10:41:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(64, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Shortlisted', '', '2025-01-11 10:41:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(65, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Shortlisted', '', '2025-01-11 10:41:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(66, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Shortlisted', '', '2025-01-11 10:41:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(67, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Shortlisted', '', '2025-01-11 10:42:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(68, 26, 17, 'SK FINANCIAL ASSISSTANCE APPLICATION FORM.pdf', 12, 'Shortlisted', '', '2025-01-11 10:44:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_metrics`
--

CREATE TABLE `job_metrics` (
  `job_post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `filled_date` date DEFAULT NULL,
  `time_to_fill` int(11) DEFAULT NULL,
  `total_applicants` int(11) DEFAULT NULL,
  `screened_applicants` int(11) DEFAULT NULL,
  `interviewed_applicants` int(11) DEFAULT NULL,
  `offered_applicants` int(11) DEFAULT NULL,
  `successful_placements` int(11) DEFAULT NULL,
  `rejected_applicants` int(11) DEFAULT NULL,
  `withdrawn_applicants` int(11) DEFAULT NULL,
  `referral_applicants` int(11) DEFAULT NULL,
  `social_media_applicants` int(11) DEFAULT NULL,
  `career_site_applicants` int(11) DEFAULT NULL,
  `applicant_to_hire_ratio` decimal(10,2) DEFAULT NULL,
  `dropout_rate` decimal(10,2) DEFAULT NULL,
  `referral_success_rate` decimal(10,2) DEFAULT NULL,
  `social_media_success_rate` decimal(10,2) DEFAULT NULL,
  `career_site_success_rate` decimal(10,2) DEFAULT NULL,
  `avg_duration_applied_to_screened` int(11) DEFAULT NULL,
  `avg_duration_screened_to_interviewed` int(11) DEFAULT NULL,
  `avg_duration_interviewed_to_offered` int(11) DEFAULT NULL,
  `avg_duration_offered_to_hired` int(11) DEFAULT NULL,
  `avg_total_duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_metrics`
--

INSERT INTO `job_metrics` (`job_post_id`, `created_at`, `filled_date`, `time_to_fill`, `total_applicants`, `screened_applicants`, `interviewed_applicants`, `offered_applicants`, `successful_placements`, `rejected_applicants`, `withdrawn_applicants`, `referral_applicants`, `social_media_applicants`, `career_site_applicants`, `applicant_to_hire_ratio`, `dropout_rate`, `referral_success_rate`, `social_media_success_rate`, `career_site_success_rate`, `avg_duration_applied_to_screened`, `avg_duration_screened_to_interviewed`, `avg_duration_interviewed_to_offered`, `avg_duration_offered_to_hired`, `avg_total_duration`) VALUES
(23, '2025-01-09 12:50:04', NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, '2025-01-09 13:44:37', NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, 1, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, '2025-01-09 18:41:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, '2025-01-09 18:54:01', NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_posts`
--

CREATE TABLE `job_posts` (
  `job_post_id` int(11) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `partner_company` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `min_salary` decimal(10,2) DEFAULT NULL,
  `max_salary` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `openings` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('Open','Closed','Pending') DEFAULT 'Open',
  `preferred_educational_level` enum('ALS Graduate','High School Graduate','Junior High School Graduate','Senior High School Graduate','College Graduate','Bachelor''s Degree','Masteral Degree','Doctorate Degree') DEFAULT NULL,
  `preferred_age_range` varchar(50) DEFAULT NULL,
  `preferred_work_experience` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_posts`
--

INSERT INTO `job_posts` (`job_post_id`, `job_title`, `partner_company`, `location`, `min_salary`, `max_salary`, `description`, `openings`, `created_by`, `deadline`, `status`, `preferred_educational_level`, `preferred_age_range`, `preferred_work_experience`) VALUES
(23, 'Job 1', 'Company 1', 'Location 1', 100.00, 199.00, 'Description 1', 50, 9, '2025-01-30', 'Open', 'Masteral Degree', '20 - 30', '10'),
(24, 'Job 2', 'Company 2', 'Address 2', 200.00, 997.00, 'Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 Description 2 ', 5005, 9, '2025-01-22', 'Open', 'Senior High School Graduate', '10 - 20', '30'),
(25, 'Job 3', 'Company 3', 'Address 3', 0.00, 0.00, 'This is a sample description.', 33, 9, '2025-01-29', 'Open', 'ALS Graduate', '', ''),
(26, 'Job 4', 'Company 4', 'Address 4', 0.00, 0.00, 'Description 4', 299, 9, '2025-01-29', 'Open', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `job_requirements`
--

CREATE TABLE `job_requirements` (
  `requirement_id` int(11) NOT NULL,
  `job_post_id` int(11) DEFAULT NULL,
  `requirement_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_requirements`
--

INSERT INTO `job_requirements` (`requirement_id`, `job_post_id`, `requirement_name`) VALUES
(38, 23, 'Requirement 1'),
(39, 23, 'Requirement 2');

-- --------------------------------------------------------

--
-- Table structure for table `questionnaires`
--

CREATE TABLE `questionnaires` (
  `question_id` int(11) NOT NULL,
  `job_post_id` int(11) DEFAULT NULL,
  `question_text` text NOT NULL,
  `dealbreaker` tinyint(1) DEFAULT 0,
  `correct_answer` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questionnaires`
--

INSERT INTO `questionnaires` (`question_id`, `job_post_id`, `question_text`, `dealbreaker`, `correct_answer`) VALUES
(31, 23, 'Question 1', 1, 1),
(32, 23, 'Question 2', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_answers`
--

CREATE TABLE `questionnaire_answers` (
  `answer_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questionnaire_answers`
--

INSERT INTO `questionnaire_answers` (`answer_id`, `application_id`, `question_id`, `answer_text`, `is_correct`) VALUES
(70, 58, 31, '1', 1),
(71, 58, 32, '0', 1);

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int(11) NOT NULL,
  `referrer_id` int(11) DEFAULT NULL,
  `referred_user_id` int(11) DEFAULT NULL,
  `job_post_id` int(11) DEFAULT NULL,
  `referrer_user_role` enum('Applicant','Recruiter') DEFAULT NULL,
  `referral_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Successful','Failed') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `referrer_id`, `referred_user_id`, `job_post_id`, `referrer_user_role`, `referral_date`, `status`) VALUES
(3, 16, 13, NULL, 'Applicant', '2025-01-02 10:35:58', 'Successful');

-- --------------------------------------------------------

--
-- Table structure for table `referral_points`
--

CREATE TABLE `referral_points` (
  `points_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_points` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referral_points`
--

INSERT INTO `referral_points` (`points_id`, `user_id`, `total_points`, `last_updated`) VALUES
(1, 16, 1, '2025-01-02 10:39:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `facebook_messenger_link` varchar(255) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `referral_code` varchar(255) NOT NULL,
  `cellphone_number` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` enum('Male','Female','Other') DEFAULT NULL,
  `height_ft` decimal(3,2) DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed','Separated') DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `has_tattoo` tinyint(1) DEFAULT NULL,
  `covid_vaccination_status` enum('Vaccinated','Unvaccinated') DEFAULT NULL,
  `id_picture_reference` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `facebook_messenger_link`, `email_address`, `referral_code`, `cellphone_number`, `first_name`, `last_name`, `middle_name`, `nickname`, `address`, `birthday`, `birth_place`, `age`, `sex`, `height_ft`, `marital_status`, `religion`, `has_tattoo`, `covid_vaccination_status`, `id_picture_reference`) VALUES
(16, 'https://www.facebook.com/', 'joshuaramos040@gmail.com', '02PA85UG', '+63333333333', 'Joshua', 'Ramos', 'Modeloso', 'Watok', 'Purok 4, San Isidro, Cabuyao City, Laguna, Region IV-A (CALABARZON)', '2025-01-02', 'Cabuyao', 22, 'Male', 5.80, 'Single', 'none', 0, 'Vaccinated', 'uploads/profile_pictures/67766b63e9035_86237-13.jpg'),
(17, 'https://pinnacle.pnc.edu.ph/student/quiz/FPOiRwVjrXFg2lPbsB4T3YenBeBwB4kVk2N5AeHlKkeENGtoMtcj2uNvQdA0b_4P', 'ramosjoshua0605@gmail.com', 'Q82BE5VR', '+631222222222', 'Ramos', 'Joshua', 'Modeloso', 'Sample', 'Purok 4, San Isidro, Cabuyao City, Laguna, Region IV-A (CALABARZON)', '2025-01-23', 'Calamba', 22, 'Male', 2.30, 'Divorced', 'Agnostic', 0, 'Vaccinated', 'uploads/profile_pictures/6776746c4ff83_462646575_1303415990837939_4950981661641410905_n.png');

-- --------------------------------------------------------

--
-- Table structure for table `user_certifications`
--

CREATE TABLE `user_certifications` (
  `certification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `certification_name` varchar(255) DEFAULT NULL,
  `certification_institute` varchar(255) DEFAULT NULL,
  `year_taken_certification` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_education`
--

CREATE TABLE `user_education` (
  `education_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `highest_educational_attainment` enum('ALS Graduate','High School Graduate (Old Curriculum)','Junior High School Graduate','Senior High School Graduate','College Graduate','College Undergraduate','Bachelor''s Degree','Masteral Degree','Other') DEFAULT NULL,
  `junior_high_school` varchar(255) DEFAULT NULL,
  `year_graduated_junior_highschool` year(4) DEFAULT NULL,
  `senior_high_school` varchar(255) DEFAULT NULL,
  `year_graduated_senior_highschool` year(4) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `year_graduated_college` year(4) DEFAULT NULL,
  `course_program` varchar(255) DEFAULT NULL,
  `postgrad_masters` varchar(255) DEFAULT NULL,
  `year_graduated_postgrad_masters` year(4) DEFAULT NULL,
  `other_details` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_education`
--

INSERT INTO `user_education` (`education_id`, `user_id`, `highest_educational_attainment`, `junior_high_school`, `year_graduated_junior_highschool`, `senior_high_school`, `year_graduated_senior_highschool`, `college`, `year_graduated_college`, `course_program`, `postgrad_masters`, `year_graduated_postgrad_masters`, `other_details`) VALUES
(20, 17, 'Masteral Degree', '123', '2000', '123', '2000', '123', '1997', '123', '123', '2000', '');

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE `user_logins` (
  `login_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Applicant','Recruiter','Admin') DEFAULT 'Applicant',
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('Banned','Active','Inactive') DEFAULT 'Inactive',
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiration` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logins`
--

INSERT INTO `user_logins` (`login_id`, `user_id`, `email`, `password_hash`, `role`, `last_login`, `status`, `verification_token`, `reset_token`, `reset_token_expiration`) VALUES
(9, 16, 'joshuaramos040@gmail.com', '$2y$10$GIxmGpgtglqS7s9EhVuzauxmFdY3fhlSM31G3eEOLtzOuZcDEp5Hi', 'Recruiter', '2025-01-11 10:50:13', 'Active', '', NULL, NULL),
(13, 17, 'ramosjoshua0605@gmail.com', '$2y$10$YHuRHHkU12UY4yD5LE.8ieFxggykK.AbIE7jGsbq.84zqAPFGd.hW', 'Applicant', '2025-01-11 10:30:29', 'Active', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_work_experience`
--

CREATE TABLE `user_work_experience` (
  `experience_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `years_worked` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `job_post_id` (`job_post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `job_metrics`
--
ALTER TABLE `job_metrics`
  ADD PRIMARY KEY (`job_post_id`);

--
-- Indexes for table `job_posts`
--
ALTER TABLE `job_posts`
  ADD PRIMARY KEY (`job_post_id`),
  ADD KEY `job_posts_ibfk_1` (`created_by`);

--
-- Indexes for table `job_requirements`
--
ALTER TABLE `job_requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `job_post_id` (`job_post_id`);

--
-- Indexes for table `questionnaires`
--
ALTER TABLE `questionnaires`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `job_post_id` (`job_post_id`);

--
-- Indexes for table `questionnaire_answers`
--
ALTER TABLE `questionnaire_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `referrer_id` (`referrer_id`),
  ADD KEY `job_post_id` (`job_post_id`),
  ADD KEY `fk_referrals_referred_user` (`referred_user_id`);

--
-- Indexes for table `referral_points`
--
ALTER TABLE `referral_points`
  ADD PRIMARY KEY (`points_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email_address` (`email_address`);

--
-- Indexes for table `user_certifications`
--
ALTER TABLE `user_certifications`
  ADD PRIMARY KEY (`certification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_education`
--
ALTER TABLE `user_education`
  ADD PRIMARY KEY (`education_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD PRIMARY KEY (`login_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_logins_ibfk_1` (`user_id`);

--
-- Indexes for table `user_work_experience`
--
ALTER TABLE `user_work_experience`
  ADD PRIMARY KEY (`experience_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `job_posts`
--
ALTER TABLE `job_posts`
  MODIFY `job_post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `job_requirements`
--
ALTER TABLE `job_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `questionnaires`
--
ALTER TABLE `questionnaires`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `questionnaire_answers`
--
ALTER TABLE `questionnaire_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `referral_points`
--
ALTER TABLE `referral_points`
  MODIFY `points_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_certifications`
--
ALTER TABLE `user_certifications`
  MODIFY `certification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_education`
--
ALTER TABLE `user_education`
  MODIFY `education_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_work_experience`
--
ALTER TABLE `user_work_experience`
  MODIFY `experience_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_post_id`) REFERENCES `job_posts` (`job_post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_metrics`
--
ALTER TABLE `job_metrics`
  ADD CONSTRAINT `job_metrics_ibfk_1` FOREIGN KEY (`job_post_id`) REFERENCES `job_posts` (`job_post_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_posts`
--
ALTER TABLE `job_posts`
  ADD CONSTRAINT `job_posts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user_logins` (`login_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_requirements`
--
ALTER TABLE `job_requirements`
  ADD CONSTRAINT `job_requirements_ibfk_1` FOREIGN KEY (`job_post_id`) REFERENCES `job_posts` (`job_post_id`) ON DELETE CASCADE;

--
-- Constraints for table `questionnaires`
--
ALTER TABLE `questionnaires`
  ADD CONSTRAINT `questionnaires_ibfk_1` FOREIGN KEY (`job_post_id`) REFERENCES `job_posts` (`job_post_id`) ON DELETE CASCADE;

--
-- Constraints for table `questionnaire_answers`
--
ALTER TABLE `questionnaire_answers`
  ADD CONSTRAINT `questionnaire_answers_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`),
  ADD CONSTRAINT `questionnaire_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questionnaires` (`question_id`);

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `fk_referrals_referred_user` FOREIGN KEY (`referred_user_id`) REFERENCES `user_logins` (`login_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_3` FOREIGN KEY (`job_post_id`) REFERENCES `job_posts` (`job_post_id`) ON DELETE CASCADE;

--
-- Constraints for table `referral_points`
--
ALTER TABLE `referral_points`
  ADD CONSTRAINT `referral_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_certifications`
--
ALTER TABLE `user_certifications`
  ADD CONSTRAINT `user_certifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_education`
--
ALTER TABLE `user_education`
  ADD CONSTRAINT `user_education_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD CONSTRAINT `user_logins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_work_experience`
--
ALTER TABLE `user_work_experience`
  ADD CONSTRAINT `user_work_experience_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
