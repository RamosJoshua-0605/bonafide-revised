-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2025 at 05:13 PM
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
-- Table structure for table `checked_requirements`
--

CREATE TABLE `checked_requirements` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `requirement` varchar(255) NOT NULL,
  `checked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deployment_details`
--

CREATE TABLE `deployment_details` (
  `deployment_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `deployment_date` date NOT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deployment_details`
--

INSERT INTO `deployment_details` (`deployment_id`, `application_id`, `deployment_date`, `remarks`) VALUES
(1, 1, '2025-03-26', '123123123'),
(2, 1, '2025-03-20', 'asdasd'),
(3, 1, '2025-03-19', '123123123123123123123');

-- --------------------------------------------------------

--
-- Table structure for table `interview_details`
--

CREATE TABLE `interview_details` (
  `interview_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `meeting_type` enum('face-to-face','online') NOT NULL,
  `interview_date` date NOT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `recruiter_email` varchar(255) NOT NULL,
  `interview_time` time NOT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_details`
--

INSERT INTO `interview_details` (`interview_id`, `application_id`, `meeting_type`, `interview_date`, `meeting_link`, `recruiter_email`, `interview_time`, `remarks`) VALUES
(1, 1, 'face-to-face', '2025-03-28', '', '2@g.c', '11:11:00', '11231231'),
(2, 1, 'online', '2025-04-01', 'https://pinnacle.pnc.edu.ph/student/login', '2@g.c', '11:11:00', '11111'),
(3, 1, 'face-to-face', '2025-03-22', '', '12@g.c', '12:31:00', '2131231231'),
(4, 1, 'face-to-face', '2025-03-22', '', '12@g.c', '12:31:00', '2131231231'),
(5, 1, 'face-to-face', '0000-00-00', '', '1231@f.c', '12:31:00', '2313123'),
(6, 1, 'face-to-face', '2025-03-28', '', '12@g.c', '12:31:00', '123123123'),
(7, 1, 'face-to-face', '2025-03-28', '', '12@g.c', '12:31:00', '123123123'),
(8, 1, 'face-to-face', '2025-03-28', '', '12@g.c', '12:31:00', '123123123'),
(9, 1, 'face-to-face', '1232-03-12', '', '2312@g.c', '14:22:00', '3223'),
(10, 1, 'face-to-face', '2025-03-29', '', '122@g.c', '11:11:00', '23123123123');

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
(1, 1, 17, '6849.pdf', 1321, 'Withdrawn', 'test testing', '2025-03-24 03:55:14', '2025-03-24 13:56:02', NULL, '2025-03-24 13:46:26', '2025-03-24 08:32:34', NULL, '2025-03-24 14:16:06', 0, NULL, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `job_metrics`
--

CREATE TABLE `job_metrics` (
  `job_post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `filled_date` date DEFAULT NULL,
  `time_to_fill` int(11) DEFAULT NULL,
  `total_applicants` int(11) DEFAULT 0,
  `screened_applicants` int(11) DEFAULT 0,
  `interviewed_applicants` int(11) DEFAULT 0,
  `offered_applicants` int(11) DEFAULT 0,
  `successful_placements` int(11) DEFAULT 0,
  `rejected_applicants` int(11) DEFAULT 0,
  `withdrawn_applicants` int(11) DEFAULT 0,
  `referral_applicants` int(11) DEFAULT 0,
  `social_media_applicants` int(11) DEFAULT 0,
  `career_site_applicants` int(11) DEFAULT 0,
  `applicant_to_hire_ratio` decimal(10,2) DEFAULT 0.00,
  `dropout_rate` decimal(10,2) DEFAULT 0.00,
  `referral_success_rate` decimal(10,2) DEFAULT 0.00,
  `social_media_success_rate` decimal(10,2) DEFAULT 0.00,
  `career_site_success_rate` decimal(10,2) DEFAULT 0.00,
  `avg_duration_applied_to_screened` int(11) DEFAULT 0,
  `avg_duration_screened_to_interviewed` int(11) DEFAULT 0,
  `avg_duration_interviewed_to_offered` int(11) DEFAULT 0,
  `avg_duration_offered_to_hired` int(11) DEFAULT 0,
  `avg_total_duration` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_metrics`
--

INSERT INTO `job_metrics` (`job_post_id`, `created_at`, `filled_date`, `time_to_fill`, `total_applicants`, `screened_applicants`, `interviewed_applicants`, `offered_applicants`, `successful_placements`, `rejected_applicants`, `withdrawn_applicants`, `referral_applicants`, `social_media_applicants`, `career_site_applicants`, `applicant_to_hire_ratio`, `dropout_rate`, `referral_success_rate`, `social_media_success_rate`, `career_site_success_rate`, `avg_duration_applied_to_screened`, `avg_duration_screened_to_interviewed`, `avg_duration_interviewed_to_offered`, `avg_duration_offered_to_hired`, `avg_total_duration`) VALUES
(1, '2025-03-24 03:54:57', NULL, NULL, 1, 10, 10, 3, 1, 0, 1, 1, 0, 0, 0.00, 100.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0);

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
(1, '123123', '123123', '1231', 1231.00, 1232312.00, '123', 31231, 9, '2025-03-28', 'Open', 'Bachelor\'s Degree', '3 - 22', '123123');

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
(1, 1, 'req1'),
(2, 1, 'r2');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subject` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `subject`, `link`, `is_read`, `created_at`) VALUES
(1, 9, 'New Job Application', 'A new application has been submitted for the job post: 123123', 'view_application_details.php?application_id=1', 1, '2025-03-24 03:55:14'),
(2, 9, 'Application Withdrawn', 'An applicant has withdrawn their application.', 'view_application_details.php?application_id=1', 1, '2025-03-24 13:23:17'),
(3, 17, 'Interview Scheduled', 'An interview has been scheduled for you application.', 'http://localhost/bonafide/applicant/application_details.php?application_id=1', 1, '2025-03-24 13:56:02'),
(4, 9, 'Application Withdrawn', 'An applicant has withdrawn their application.', 'view_application_details.php?application_id=1', 1, '2025-03-24 14:16:06');

-- --------------------------------------------------------

--
-- Table structure for table `offer_details`
--

CREATE TABLE `offer_details` (
  `offer_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `benefits` text DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offer_details`
--

INSERT INTO `offer_details` (`offer_id`, `application_id`, `salary`, `start_date`, `benefits`, `remarks`) VALUES
(1, 1, 123123.00, '2025-04-01', 'eqweqwe', 'eqweqwe'),
(2, 1, 12312.00, '2025-04-02', '1231', '23123123'),
(3, 1, 123123.00, '2025-04-03', '123123', '123123');

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
(1, 1, 'q1', 0, 0),
(2, 1, 'q2', 1, 1);

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
(1, 1, 1, '1', 0),
(2, 1, 2, '0', 0);

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
(3, 16, 13, NULL, 'Applicant', '2025-01-02 10:35:58', 'Pending'),
(5, 39, 9, NULL, 'Applicant', '2025-03-09 03:55:59', 'Successful');

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
(16, 'https://www.facebook.com/', 'joshuaramos040@gmail.com', '02PA85UG', '+63333333333', 'Joshua', 'Ramos', 'Modeloso', 'Watok', 'Purok 4, San Isidro, Cabuyao City, Laguna, Region IV-A (CALABARZON)', '2025-01-02', 'Cabuyao', 22, 'Male', 5.80, 'Single', 'none', 0, 'Vaccinated', 'uploads/profile_pictures/image.png'),
(17, 'https://pinnacle.pnc.edu.ph/student/quiz/FPOiRwVjrXFg2lPbsB4T3YenBeBwB4kVk2N5AeHlKkeENGtoMtcj2uNvQdA0b_4P', 'ramosjoshua0605@gmail.com', 'Q82BE5VR', '+631222222222', 'Ramos', 'Joshua', NULL, 'Sample', 'Purok 4, San Isidro, Cabuyao City, Laguna, Region IV-A (CALABARZON)', '2025-01-23', 'Calamba', NULL, NULL, 2.30, 'Divorced', NULL, 0, 'Vaccinated', 'uploads/profile_pictures/67e021ace6788_vlcsnap-2025-02-08-22h31m48s549.png'),
(38, 'link 1', 'email 1', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(39, 'link 1', 'email 2', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(40, 'link 1', 'email 3', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(41, 'link 1', 'email 4', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(42, 'link 1', 'email 5', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(43, 'link 1', 'email 9', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(44, 'link 1', 'email 8', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(45, 'link 1', 'email 7', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(46, 'link 1', 'email 10', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(47, 'link 1', 'email 11', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(48, 'link 1', 'email 12', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(49, 'link 1', 'email 13', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(50, 'link 1', 'email 14', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(51, 'link 1', 'email 15', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(52, 'link 1', 'email 16', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(53, 'link 1', 'email 17', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123'),
(54, 'link 1', 'email 18', '123123', '123123', '12312312', '123', '12312', '123123', '1231231', '2025-01-22', '123123', 12, 'Male', NULL, 'Married', '123123', 0, 'Vaccinated', '3123');

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

--
-- Dumping data for table `user_certifications`
--

INSERT INTO `user_certifications` (`certification_id`, `user_id`, `certification_name`, `certification_institute`, `year_taken_certification`) VALUES
(26, 16, 'CERTIFICATE 1', 'TRAINING 1', '2002'),
(27, 16, 'CERTIFICATE 3', 'TRAINING 1', '2020'),
(28, 17, '111111111111111', '11111111111111', '2002'),
(29, 17, '2222222222222222', '2003', '2003');

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
(9, 16, 'joshuaramos040@gmail.com', '$2y$10$jIDYLfR4vm4607u1tQs9Ten9I9tviv99VLuLPgBebOrdQNYna7L3W', 'Recruiter', '2025-03-24 14:34:26', 'Active', '', '68e66627de30ca9ddf8151e2244e688a', '2025-03-25 15:14:14'),
(13, 17, 'ramosjoshua0605@gmail.com', '$2y$10$bM27I0wnq4GYy8SFSHrCjO70F7LRGYmCLMNF2zfSInmeDn6utTPdK', 'Applicant', '2025-03-24 15:52:05', 'Active', NULL, '864b06f658aa9d416a37a7b290598425', '2025-03-24 08:29:32'),
(15, NULL, 'ramosjoshua151@gmail.com', '$2y$10$Rp8pk2j39fjST3jyazTY1Op4kE8KXrTr7IQ37/SKfu/6z/1464aC6', 'Admin', '2025-03-24 15:28:48', 'Active', NULL, NULL, NULL),
(16, NULL, 'test@g.c', '$2y$10$QGoUrLyhjhNMx2Key8jJXOWbcVpLPIdnJAizY.ttbn/o3cnF6UnXO', 'Applicant', '2025-03-24 16:08:42', 'Active', NULL, NULL, NULL);

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
-- Dumping data for table `user_work_experience`
--

INSERT INTO `user_work_experience` (`experience_id`, `user_id`, `company_name`, `role`, `years_worked`) VALUES
(9, 17, 'Experience 1', 'role 1222', 5),
(11, 17, '31231231', '23123', 90);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `checked_requirements`
--
ALTER TABLE `checked_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `deployment_details`
--
ALTER TABLE `deployment_details`
  ADD PRIMARY KEY (`deployment_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `interview_details`
--
ALTER TABLE `interview_details`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `application_id` (`application_id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `offer_details`
--
ALTER TABLE `offer_details`
  ADD PRIMARY KEY (`offer_id`),
  ADD KEY `application_id` (`application_id`);

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
  ADD KEY `questionnaire_answers_ibfk_2` (`question_id`);

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
-- AUTO_INCREMENT for table `checked_requirements`
--
ALTER TABLE `checked_requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deployment_details`
--
ALTER TABLE `deployment_details`
  MODIFY `deployment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `interview_details`
--
ALTER TABLE `interview_details`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_posts`
--
ALTER TABLE `job_posts`
  MODIFY `job_post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_requirements`
--
ALTER TABLE `job_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `offer_details`
--
ALTER TABLE `offer_details`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `questionnaires`
--
ALTER TABLE `questionnaires`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `questionnaire_answers`
--
ALTER TABLE `questionnaire_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `referral_points`
--
ALTER TABLE `referral_points`
  MODIFY `points_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `user_certifications`
--
ALTER TABLE `user_certifications`
  MODIFY `certification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_education`
--
ALTER TABLE `user_education`
  MODIFY `education_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_work_experience`
--
ALTER TABLE `user_work_experience`
  MODIFY `experience_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `checked_requirements`
--
ALTER TABLE `checked_requirements`
  ADD CONSTRAINT `checked_requirements_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`) ON DELETE CASCADE;

--
-- Constraints for table `deployment_details`
--
ALTER TABLE `deployment_details`
  ADD CONSTRAINT `deployment_details_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`);

--
-- Constraints for table `interview_details`
--
ALTER TABLE `interview_details`
  ADD CONSTRAINT `interview_details_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`);

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
-- Constraints for table `offer_details`
--
ALTER TABLE `offer_details`
  ADD CONSTRAINT `offer_details_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`);

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
  ADD CONSTRAINT `questionnaire_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questionnaires` (`question_id`) ON DELETE CASCADE;

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
