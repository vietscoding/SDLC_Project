-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 11, 2025 lúc 11:38 AM
-- Phiên bản máy phục vụ: 8.0.42
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `lms`
--
CREATE DATABASE IF NOT EXISTS `lms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lms`;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `assignments`
--

CREATE TABLE `assignments` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `file_attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `assignments`
--

INSERT INTO `assignments` (`id`, `course_id`, `title`, `description`, `due_date`, `created_at`, `file_attachment`, `file_path`) VALUES
(1, 1, 'HTML Basics', 'Create a simple HTML website', '2025-05-31', '2025-05-16 19:18:50', NULL, NULL),
(2, 4, 'C# console program', 'Create basic C# console program', '2025-06-30', '2025-06-05 13:53:19', NULL, 'uploads/assignments/1749106399_Assigment1.docx');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int NOT NULL,
  `assignment_id` int NOT NULL,
  `user_id` int NOT NULL,
  `submitted_text` text COLLATE utf8mb4_unicode_ci,
  `submitted_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `grade` float DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `assignment_submissions`
--

INSERT INTO `assignment_submissions` (`id`, `assignment_id`, `user_id`, `submitted_text`, `submitted_file`, `submitted_at`, `grade`, `feedback`) VALUES
(1, 1, 1, 'dsfsd', '', '2025-06-05 14:11:30', 8, 'Good'),
(2, 1, 3, '11111111', '', '2025-05-17 11:50:27', 10, 'Good'),
(3, 2, 3, 'isfhjsdbfsfsd', 'uploads/assignments/1749108794_BD00812_HoangAnhQuan_SE07203_7419_ASM_Final.docx', '2025-06-05 14:33:14', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `courses`
--

CREATE TABLE `courses` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `teacher_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `courses`
--

INSERT INTO `courses` (`id`, `title`, `department`, `description`, `teacher_id`, `created_at`) VALUES
(1, 'HTML Basics', 'IT', 'Learn the basics of HTML.', 2, '2025-05-16 14:45:21'),
(2, 'CSS Styling', 'IT', 'Introduction to CSS styling for web pages.', 2, '2025-05-16 14:45:21'),
(3, 'JavaScript', 'IT', 'Basic course for beginner', 4, '2025-05-17 14:00:47'),
(4, 'C# Programming', 'IT', 'Building Applications with C#', 2, '2025-05-18 13:39:30'),
(5, 'Java core', 'IT', 'Java Core Concepts and Applications', 4, '2025-05-18 13:40:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `enrolled_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `enrollments`
--

INSERT INTO `enrollments` (`id`, `user_id`, `course_id`, `enrolled_at`) VALUES
(1, 1, 2, '2025-05-16 15:02:17'),
(2, 1, 1, '2025-05-16 15:03:32'),
(3, 3, 1, '2025-05-16 16:39:01'),
(4, 3, 2, '2025-05-17 11:22:41'),
(5, 1, 4, '2025-06-05 13:54:01'),
(6, 3, 4, '2025-06-05 14:18:41'),
(7, 1, 3, '2025-06-10 00:00:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `forum_posts`
--

CREATE TABLE `forum_posts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `posted_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `forum_posts`
--

INSERT INTO `forum_posts` (`id`, `user_id`, `course_id`, `content`, `posted_at`) VALUES
(1, 1, 2, 'hello', '2025-05-16 15:47:36'),
(2, 1, 1, 'hello world', '2025-05-16 16:20:22'),
(3, 3, 2, 'Hi', '2025-05-17 11:39:19'),
(4, 3, 1, 'Hi', '2025-05-17 11:50:13'),
(5, 1, 1, 'sgsdgsd', '2025-05-17 16:18:39'),
(6, 1, 1, 'kmdsnfkjsdnfk', '2025-06-09 13:47:11');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lessons`
--

CREATE TABLE `lessons` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `video_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `content`, `video_link`, `created_at`, `file_path`) VALUES
(1, 1, 'Introduction to HTML', 'This is the first lesson about HTML.', 'https://www.youtube.com/embed/dD2EISBDjWM', '2025-05-16 14:47:07', 'uploads/lessons/1749132861_Chapter6_Examples.docx'),
(2, 1, 'HTML Tags', 'Explanation of basic HTML tags.', 'https://www.youtube.com/watch?v=DJ0Pehp_U48&pp=ygUQI2FsbHRhZ3NzdHJ1a3R1cg%3D%3D', '2025-05-16 14:47:07', 'uploads/lessons/1749110104_Chapter6_Examples.docx'),
(4, 4, 'Introduction to C#', 'C# was launched in 2000 by Microsoft. It was designed to be a more modern version of C/C++ and has a similar syntax and an object-oriented approach. C# is a type-safe language and memory management is handled by garbage collectors. C# programs are portable in the same way C/C++ programs are. All these characteristics allowed new and experienced programmers to easily learn C#. Systems developed in C/C++ could be translated and modernized with C#.  \r\n\r\nC# introduced standardized support for international languages. One of the most critical goals was to develop a language to build web browsers and applications. C# uses Common Language Runtime and the .NET framework, Microsoft’s virtual environment, to do this.\r\n\r\nC# was not designed to compete with the size and speed of programs developed with C or lower-level assembly languages.\r\n\r\nWhat is a virtual environment?\r\nImagine playing Tic-Tac-Toe on your computer. No internet connection or connections to other machines. You and a friend passing the keyboard back and forth taking turns, in the same room side-by-side. No connectivity and no virtual network.\r\n\r\nNow connect your computer to another computer on a network — still in the same room. No internet connection. A virtualized version of the same game would allow each machine to share that game. Each machine has the same visuals and uses the mouse and keystrokes from both machines. This is 1980s universities and businesses. Lots of computers connected and hard-wired in local-area networks.\r\n\r\nIntroduce web programs and virtualization. A web browser allows the program to run on any machine in the world and the game can be developed to allow any number of people to join. The web browser is a virtual environment — unrelated to your PC or the machine the game is running on. It is self-contained.\r\n\r\nThis was an amazing period in computing history, opening the gateway to developing so many of the ideas and programs we use in our everyday lives.', 'https://www.youtube.com/watch?v=GhQdlIFylQ8', '2025-05-18 13:49:24', NULL),
(5, 4, 'Variables and Data Types', 'Numbers\r\nNumber types are divided into two groups:\r\n\r\nInteger types stores whole numbers, positive or negative (such as 123 or -456), without decimals. Valid types are int and long. Which type you should use, depends on the numeric value.\r\n\r\nFloating point types represents numbers with a fractional part, containing one or more decimals. Valid types are float and double.\r\n\r\nEven though there are many numeric types in C#, the most used for numbers are int (for whole numbers) and double (for floating point numbers). However, we will describe them all as you continue to read.\r\n\r\nInteger Types\r\nInt\r\nThe int data type can store whole numbers from -2147483648 to 2147483647. In general, and in our tutorial, the int data type is the preferred data type when we create variables with a numeric value.\r\n\r\nExample\r\nint myNum = 100000;\r\nConsole.WriteLine(myNum);\r\n\r\nLong\r\nThe long data type can store whole numbers from -9223372036854775808 to 9223372036854775807. This is used when int is not large enough to store the value. Note that you should end the value with an \"L\":\r\n\r\nExample\r\nlong myNum = 15000000000L;\r\nConsole.WriteLine(myNum);\r\n\r\nFloating Point Types\r\nYou should use a floating point type whenever you need a number with a decimal, such as 9.99 or 3.14515.\r\n\r\nThe float and double data types can store fractional numbers. Note that you should end the value with an \"F\" for floats and \"D\" for doubles:\r\n\r\nFloat Example\r\nfloat myNum = 5.75F;\r\nConsole.WriteLine(myNum);\r\n\r\nDouble Example\r\ndouble myNum = 19.99D;\r\nConsole.WriteLine(myNum);\r\n\r\nUse float or double?\r\n\r\nThe precision of a floating point value indicates how many digits the value can have after the decimal point. The precision of float is only six or seven decimal digits, while double variables have a precision of about 15 digits. Therefore it is safer to use double for most calculations.\r\n\r\nScientific Numbers\r\nA floating point number can also be a scientific number with an \"e\" to indicate the power of 10:\r\n\r\nExample\r\nfloat f1 = 35e3F;\r\ndouble d1 = 12E4D;\r\nConsole.WriteLine(f1);\r\nConsole.WriteLine(d1);', 'https://www.youtube.com/watch?v=OAqQxC_piRs', '2025-05-18 13:52:12', NULL),
(6, 4, 'Operators and Expressions', '- Arithmetic, relational, logical, and bitwise operators\r\n- Operator precedence', 'https://www.youtube.com/watch?v=n6YoXxLZeSU&pp=ygUcT3BlcmF0b3JzIGFuZCBFeHByZXNzaW9ucyBjIw%3D%3D', '2025-05-18 13:53:07', NULL),
(7, 4, 'Control Statements', '1. if, else if, else\r\n2. switch-case\r\n3. loops: for, while, do-while, foreach', 'https://www.youtube.com/watch?v=pSPQnXleaS8&pp=ygUVQ29udHJvbCBTdGF0ZW1lbnRzIGMj0gcJCY0JAYcqIYzv', '2025-05-18 13:53:50', NULL),
(8, 4, 'Methods and Functions', '1. Declaring methods\r\n2. Parameters and return types\r\n3. Method overloading\r\n4. Recursion', 'https://www.youtube.com/watch?v=7-uepECsiRg&pp=ygUYTWV0aG9kcyBhbmQgRnVuY3Rpb25zIGMj', '2025-05-18 13:54:36', NULL),
(9, 4, 'Object-Oriented Programming', '1. Classes and objects\r\n2. Properties and fields\r\n3. Constructors and destructors\r\n4. Encapsulation', 'https://www.youtube.com/watch?v=iA0XZwFqqKI&pp=ygUeT2JqZWN0LU9yaWVudGVkIFByb2dyYW1taW5nIGMj', '2025-05-18 13:55:33', NULL),
(10, 4, 'Inheritance and Polymorphism', '1. Base and derived classes\r\n2. Overriding methods\r\n3. Virtual and abstract methods', 'https://www.youtube.com/watch?v=CClziU97Xeg&pp=ygUfSW5oZXJpdGFuY2UgYW5kIFBvbHltb3JwaGlzbSBjIw%3D%3D', '2025-05-18 13:56:14', NULL),
(11, 4, 'Interfaces and Abstract Classes', '1. Interface declaration and implementation\r\n2. Abstract classes and methods\r\n3. Differences and use cases', 'https://www.youtube.com/watch?v=0EnSPBVrbG0&pp=ygUiSW50ZXJmYWNlcyBhbmQgQWJzdHJhY3QgQ2xhc3NlcyBjIw%3D%3D', '2025-05-18 13:57:05', NULL),
(12, 1, 'Login form in HTML', '1. Set up the HTML Document\r\n2. Create the Form Element\r\n3. Add Input Fields\r\n4. Include a Submit Button', 'https://www.youtube.com/watch?v=hlwlM4a5rxg', '2025-06-05 14:39:33', 'uploads/lessons/1749109173_Chapter6_Examples.docx'),
(13, 2, 'CSS Basic', '1. What is CSS?\r\n2. Applying CSS to your HTML\r\n3. CSS syntax basics\r\n4. Improving the text', '', '2025-06-05 16:32:38', 'uploads/lessons/1749115958_Chapter6_Examples.docx');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `sender_id` int DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`, `sender_id`, `course_id`, `type`) VALUES
(3, 1, 'Welcome to the HTML Basics course!', 1, '2025-05-16 16:26:24', NULL, NULL, 'general'),
(4, 1, 'You scored 2 points on quiz \'\'!', 1, '2025-05-16 16:34:21', NULL, NULL, 'general'),
(5, 3, 'You have successfully enrolled in the course: \'\'.', 1, '2025-05-16 16:39:01', NULL, NULL, 'general'),
(6, 1, 'Remember your deadline !', 1, '2025-05-16 18:45:10', NULL, NULL, 'general'),
(7, 3, 'Remember your deadline !', 1, '2025-05-16 18:45:10', NULL, NULL, 'general'),
(8, 3, 'You have successfully enrolled in the course: \'\'.', 1, '2025-05-17 11:22:41', NULL, NULL, 'general'),
(9, 2, 'Nguyễn Văn B has submitted an assignment for your course: HTML Basics', 0, '2025-05-17 11:50:27', NULL, NULL, 'general'),
(10, 3, 'You scored 1 points on quiz \'\'!', 1, '2025-05-17 11:51:03', NULL, NULL, 'general'),
(11, 2, 'Nguyễn Văn B has submitted a quiz in your course: CSS Styling', 0, '2025-05-17 11:51:03', NULL, NULL, 'general'),
(12, 1, 'You have successfully enrolled in the course: \'C# Programming\'.', 1, '2025-06-05 13:54:01', NULL, NULL, 'general'),
(13, 2, 'Hoàng Anh Quân has enrolled in your course: C# Programming', 0, '2025-06-05 13:54:01', NULL, NULL, 'general'),
(14, 2, 'Hoàng Anh Quân has submitted an assignment for your course: HTML Basics', 0, '2025-06-05 14:11:30', NULL, NULL, 'general'),
(15, 3, 'You have successfully enrolled in the course: \'C# Programming\'.', 1, '2025-06-05 14:18:41', NULL, NULL, 'general'),
(16, 2, 'Nguyễn Văn B has enrolled in your course: C# Programming', 0, '2025-06-05 14:18:41', NULL, NULL, 'general'),
(17, 2, 'Nguyễn Văn B has submitted an assignment for your course: C# Programming', 0, '2025-06-05 14:31:01', NULL, NULL, 'general'),
(18, 2, 'Nguyễn Văn B has submitted an assignment for your course: C# Programming', 0, '2025-06-05 14:32:34', NULL, NULL, 'general'),
(19, 2, 'Nguyễn Văn B has submitted an assignment for your course: C# Programming', 0, '2025-06-05 14:33:14', NULL, NULL, 'general'),
(20, 1, 'remember your deadline!', 1, '2025-06-06 17:24:27', NULL, NULL, 'general'),
(21, 3, 'remember your deadline!', 1, '2025-06-06 17:24:27', NULL, NULL, 'general'),
(22, 1, 'Remember your deadline!!!!!', 1, '2025-06-06 17:24:58', NULL, NULL, 'general'),
(23, 3, 'Remember your deadline!!!!!', 1, '2025-06-06 17:24:58', NULL, NULL, 'general'),
(24, 1, 'Remember your deadline!!!!!', 1, '2025-06-06 17:26:15', NULL, NULL, 'general'),
(25, 3, 'Remember your deadline!!!!!', 1, '2025-06-06 17:26:15', NULL, NULL, 'general'),
(26, 1, 'ưdfgfdhgfdhg', 1, '2025-06-06 17:28:38', NULL, NULL, 'general'),
(27, 3, 'ưdfgfdhgfdhg', 1, '2025-06-06 17:28:38', NULL, NULL, 'general'),
(28, 1, 'ádfsdfgsgdfghfghj', 1, '2025-06-06 17:31:05', NULL, NULL, 'general'),
(29, 3, 'ádfsdfgsgdfghfghj', 1, '2025-06-06 17:31:05', NULL, NULL, 'general'),
(30, 2, 'The system will be under maintenance tomorrow at 12pm', 0, '2025-06-06 20:21:26', NULL, NULL, 'general'),
(31, 5, 'The system will be under maintenance tomorrow at 12pm', 0, '2025-06-06 20:21:26', NULL, NULL, 'general'),
(32, 3, 'The system will be under maintenance tomorrow at 12pm', 1, '2025-06-06 20:21:26', NULL, NULL, 'general'),
(33, 4, 'The system will be under maintenance tomorrow at 12pm', 0, '2025-06-06 20:21:26', NULL, NULL, 'general'),
(34, 6, 'The system will be under maintenance tomorrow at 12pm', 0, '2025-06-06 20:21:26', NULL, NULL, 'general'),
(35, 1, 'The system will be under maintenance tomorrow at 12pm', 1, '2025-06-06 20:21:26', NULL, NULL, 'general'),
(36, 7, 'The system will be under maintenance tomorrow at 12pm', 0, '2025-06-06 20:21:26', NULL, NULL, 'general'),
(37, 1, 'You have successfully enrolled in the course: \'JavaScript\'.', 1, '2025-06-10 00:00:14', NULL, NULL, 'general'),
(38, 4, 'Hoàng Anh Quân has enrolled in your course: JavaScript', 0, '2025-06-10 00:00:14', NULL, NULL, 'general'),
(39, 1, 'From Nguyễn Văn C (Course: JavaScript): Hello student!', 1, '2025-06-10 00:01:03', 4, 3, 'teacher_notification');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `progress`
--

CREATE TABLE `progress` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `progress`
--

INSERT INTO `progress` (`id`, `user_id`, `course_id`, `lesson_id`, `is_completed`, `completed_at`) VALUES
(1, 1, 1, 1, 1, '2025-05-16 15:07:09'),
(2, 1, 1, 2, 1, '2025-05-16 15:07:17'),
(3, 3, 1, 1, 1, '2025-05-17 11:47:32'),
(4, 1, 4, 5, 1, '2025-06-05 16:26:14'),
(5, 1, 2, 13, 1, '2025-06-05 16:33:57'),
(6, 1, 1, 12, 1, '2025-06-05 16:41:56'),
(7, 1, 4, 4, 1, '2025-06-06 20:17:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quizzes`
--

INSERT INTO `quizzes` (`id`, `course_id`, `title`, `created_at`) VALUES
(1, 1, 'HTML Basics Quiz', '2025-05-16 15:11:21'),
(2, 2, 'CSS Fundamentals Quiz', '2025-05-16 15:11:21');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int NOT NULL,
  `submission_id` int NOT NULL,
  `question_id` int NOT NULL,
  `selected_option` enum('A','B','C','D') COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quiz_answers`
--

INSERT INTO `quiz_answers` (`id`, `submission_id`, `question_id`, `selected_option`) VALUES
(1, 9, 1, 'A'),
(2, 9, 2, 'C'),
(3, 10, 1, 'A'),
(4, 10, 2, 'A'),
(5, 11, 3, 'A'),
(6, 11, 4, 'A'),
(7, 12, 3, 'A'),
(8, 12, 4, 'B'),
(9, 13, 1, 'A'),
(10, 13, 2, 'A'),
(11, 14, 1, 'A'),
(12, 14, 2, 'C'),
(13, 15, 3, 'A'),
(14, 15, 4, 'A');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int NOT NULL,
  `quiz_id` int NOT NULL,
  `question` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_a` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_b` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_c` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_d` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correct_option` enum('A','B','C','D') COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES
(1, 1, 'What does HTML stand for?', 'Hyper Text Markup Language', 'Hot Mail', 'How to Make Lasagna', 'Home Tool Markup Language', 'A'),
(2, 1, 'Who is making the Web standards?', 'Mozilla', 'Microsoft', 'The World Wide Web Consortium', 'Google', 'C'),
(3, 2, 'Which property is used to change the background color?', 'color', 'background-color', 'bgcolor', 'background', 'B'),
(4, 2, 'How do you make each word in a text start with a capital letter?', 'text-transform: capitalize;', 'text-style: capital;', 'transform: capitalize;', 'You can\'t do that with CSS', 'A');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_submissions`
--

CREATE TABLE `quiz_submissions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `quiz_id` int NOT NULL,
  `score` int DEFAULT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quiz_submissions`
--

INSERT INTO `quiz_submissions` (`id`, `user_id`, `quiz_id`, `score`, `submitted_at`) VALUES
(1, 1, 1, 1, '2025-05-16 15:22:50'),
(2, 1, 1, 1, '2025-05-16 15:25:35'),
(3, 1, 1, 1, '2025-05-16 15:26:07'),
(4, 1, 2, 1, '2025-05-16 15:26:15'),
(5, 1, 1, 2, '2025-05-16 15:59:26'),
(6, 1, 1, 2, '2025-05-16 15:59:54'),
(7, 1, 1, 2, '2025-05-16 16:00:44'),
(8, 1, 1, 2, '2025-05-16 16:02:15'),
(9, 1, 1, 2, '2025-05-16 16:03:27'),
(10, 1, 1, 1, '2025-05-16 16:03:39'),
(11, 1, 2, 1, '2025-05-16 16:07:39'),
(12, 1, 2, 0, '2025-05-16 16:11:09'),
(13, 1, 1, 1, '2025-05-16 16:19:43'),
(14, 1, 1, 2, '2025-05-16 16:34:21'),
(15, 3, 2, 1, '2025-05-17 11:51:03');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `system_notifications`
--

CREATE TABLE `system_notifications` (
  `id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `system_notifications`
--

INSERT INTO `system_notifications` (`id`, `message`, `created_at`) VALUES
(1, 'The system will be under maintenance tomorrow from 10PM.', '2025-05-17 11:08:53'),
(2, 'New semester courses will open next week!', '2025-05-17 11:12:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','teacher','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'student',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('approved','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'approved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `created_at`, `status`) VALUES
(1, 'Hoàng Anh Quân', 'qh695927@gmail.com', '$2y$10$LCgi8vXDsWkw8Fn21SoIKe.t2kZJ4c8RbFW4DeK8vrj6ITL9cTXhi', 'student', '2025-05-16 14:31:17', 'approved'),
(2, 'Nguyễn Văn A', 'A@gmail.com', '$2y$10$4u4d7sgd9dNwjn5wOapDB.9fTt6eFSqU3MJa5AD4HYrPjW3tZ9s6O', 'teacher', '2025-05-16 14:38:12', 'approved'),
(3, 'Nguyễn Văn B', 'B@gmail.com', '$2y$10$qMD1uZ4YU/egHDD.IQFw/.b57dzbnXawq/qDhih4mXkc4Ypz7Im.i', 'student', '2025-05-16 16:38:43', 'approved'),
(4, 'Nguyễn Văn C', 'C@gmail.com', '$2y$10$fKL573E7rI2UhYp4hKuMP.U5GGCEEhMansqOboaPr.MYORMHH7Ooy', 'teacher', '2025-05-17 12:56:33', 'approved'),
(5, 'Admin', 'admin@gmail.com', '$2y$10$vI2YpLI.1ShVQVU2OHzQJuDckN1bfDmEPDL2/shA1ZVEmgPUIO/LK', 'admin', '2025-05-17 13:28:36', 'approved'),
(6, 'Trần Văn Hùng', 'hung@gmail.com', '$2y$10$7rdp5fVpG4jTDNpHr0TrtO1jnBKhIEMhxgZh8.Mux11/vyDZGCkxq', 'teacher', '2025-06-03 10:45:31', 'approved'),
(7, 'Nguyễn Thị Tâm', 'tam@gmail.com', '$2y$10$dKOuEF3sKXwVxPpXpaedlOgOkO4Tz2w6lgN0d0ue2VrMP.HRz9qcO', 'teacher', '2025-06-06 18:19:15', 'approved'),
(8, 'Trần Văn C', 'D@gmail.com', '$2y$10$5IFUNAHDRQ88BJMlLwQifew0xziLLOvKxcPTaaVPl.Wds6Cor8u8C', 'teacher', '2025-06-11 15:20:12', 'approved');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Chỉ mục cho bảng `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Chỉ mục cho bảng `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Chỉ mục cho bảng `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Chỉ mục cho bảng `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Chỉ mục cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Chỉ mục cho bảng `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Chỉ mục cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Chỉ mục cho bảng `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Chỉ mục cho bảng `system_notifications`
--
ALTER TABLE `system_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT cho bảng `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `system_notifications`
--
ALTER TABLE `system_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `forum_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_posts_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_ibfk_3` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD CONSTRAINT `quiz_answers_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `quiz_submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  ADD CONSTRAINT `quiz_submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_submissions_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
