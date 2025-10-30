<?php
require_once 'includes/functions.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منصة الكيمياء التعليمية | تعليم مبتكر وتفاعلي</title>
    <meta name="description" content="منصة متكاملة لتعليم الكيمياء لطلاب المرحلة الثانوية بمحتوى مميز وتفاعلي وأحدث التقنيات.">
    
    <!-- مكتبات CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    
    <style>
        /* CSS متقدم بتصميم بورتفوليو كلاسيكي */
        :root {
            --primary: #2c3e50;
            --primary-dark: #1a252f;
            --primary-light: #34495e;
            --secondary: #e74c3c;
            --secondary-dark: #c0392b;
            --accent: #3498db;
            --accent-dark: #2980b9;
            --gold: #f1c40f;
            --silver: #bdc3c7;
            --text-dark: #3c502cff;
            --text-darker: rgba(255, 255, 255, 1)
            --text-light: #ffffff;
            --text-muted: #7f8c8d;
            --bg-light: #f8f9fa;
            --bg-dark: #1c2f1aff;
            --card-light: #ffffff;
            --card-dark: #2c3e50;
            --border-light: #e9ecef;
            --border-dark: #34495e;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-dark: 0 10px 30px rgba(0, 136, 118, 0.3);
            --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .dark-mode {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --primary-light: #5dade2;
            --secondary: #e74c3c;
            --secondary-dark: #c0392b;
            --accent: #f1c40f;
            --accent-dark: #f39c12;
            --gold: #f1c40f;
            --silver: #bdc3c7;
            --text-dark: #ecf0f1;
            --text-darker: #ffffff;
            --text-light: #ffffff;
            --text-muted: #bdc3c7;
            --bg-light: #0084ffff;
            --bg-dark: #0c1f0fff;
            --card-light: #2c3e50;
            --card-dark: #34495e;
            --border-light: #34495e;
            --border-dark: #2c3e50;
            --shadow: 0 10px 30px rgba(212, 25, 25, 0.3);
            --shadow-dark: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.5s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background: var(--bg-light);
            color: var(--text-darker);
            overflow-x: hidden;
            line-height: 1.6;
            min-height: 100vh;
        }

        .dark-mode body {
            background: var(--bg-dark);
            color: var(--text-dark);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Interactive Background */
        .interactive-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            background: 
                radial-gradient(circle at 20% 80%, rgba(52, 152, 219, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(231, 76, 60, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(241, 196, 15, 0.05) 0%, transparent 50%);
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
            opacity: 0.1;
            animation: floatShape 25s infinite linear;
            filter: blur(20px);
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            right: 10%;
            animation-delay: 0s;
            background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: 20%;
            left: 5%;
            animation-delay: -8s;
            background: radial-gradient(circle, var(--secondary) 0%, transparent 70%);
        }

        .shape-3 {
            width: 250px;
            height: 250px;
            top: 50%;
            left: 15%;
            animation-delay: -16s;
            background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
        }

        @keyframes floatShape {
            0% {
                transform: translate(0, 0) rotate(0deg) scale(1);
            }
            25% {
                transform: translate(40px, -60px) rotate(90deg) scale(1.1);
            }
            50% {
                transform: translate(-30px, 40px) rotate(180deg) scale(0.9);
            }
            75% {
                transform: translate(-60px, -30px) rotate(270deg) scale(1.05);
            }
            100% {
                transform: translate(0, 0) rotate(360deg) scale(1);
            }
        }

        /* Header Styles */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.2rem 0;
            z-index: 1000;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-light);
        }

        .dark-mode .main-header {
            background: rgba(44, 62, 80, 0.95);
            border-bottom: 1px solid var(--border-dark);
        }

        .main-header.scrolled {
            padding: 0.8rem 0;
            box-shadow: var(--shadow);
        }

        .main-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-darker);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            font-size: 2rem;
            color: var(--accent);
            animation: logoSpin 8s linear infinite;
        }

        .dark-mode .logo {
            color: var(--text-dark);
        }

        .logo:hover {
            transform: scale(1.05);
        }

        @keyframes logoSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .desktop-nav {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .desktop-nav a {
            color: var(--text-darker);
            text-decoration: none;
            font-weight: 600;
            position: relative;
            padding: 0.5rem 0;
            transition: var(--transition);
            font-size: 1rem;
        }

        .dark-mode .desktop-nav a {
            color: var(--text-dark);
        }

        .desktop-nav a:after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(45deg, var(--accent), var(--secondary));
            transition: var(--transition);
        }

        .desktop-nav a:hover {
            color: var(--accent);
        }

        .dark-mode .desktop-nav a:hover {
            color: var(--accent);
        }

        .desktop-nav a:hover:after {
            width: 100%;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Theme Toggle in Header */
        .theme-toggle-header {
            background: none;
            border: none;
            color: var(--text-darker);
            font-size: 1.3rem;
            cursor: pointer;
            transition: var(--transition);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle-header:hover {
            background: var(--border-light);
            transform: rotate(15deg);
        }

        .dark-mode .theme-toggle-header {
            color: var(--text-dark);
        }

        .dark-mode .theme-toggle-header:hover {
            background: var(--border-dark);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            text-align: center;
            white-space: nowrap;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(52, 152, 219, 0.5);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-darker);
            border: 2px solid var(--accent);
        }

        .dark-mode .btn-secondary {
            color: var(--text-dark);
            border: 2px solid var(--accent);
        }

        .btn-secondary:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
        }

        .btn-lg {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-darker);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .dark-mode .mobile-menu-btn {
            color: var(--text-dark);
        }

        .mobile-menu-btn:hover {
            transform: scale(1.1);
        }

        /* Mobile Navigation */
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -100%;
            width: 85%;
            max-width: 320px;
            height: 100vh;
            background: var(--card-light);
            backdrop-filter: blur(15px);
            z-index: 1100;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            transition: var(--transition);
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .dark-mode .mobile-nav {
            background: var(--card-dark);
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.3);
        }

        .mobile-nav.active {
            right: 0;
        }

        .mobile-nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .close-menu {
            background: none;
            border: none;
            color: var(--text-darker);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .dark-mode .close-menu {
            color: var(--text-dark);
        }

        .close-menu:hover {
            transform: rotate(90deg);
        }

        .mobile-theme-toggle {
            background: none;
            border: none;
            color: var(--text-darker);
            font-size: 1.3rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .dark-mode .mobile-theme-toggle {
            color: var(--text-dark);
        }

        .mobile-theme-toggle:hover {
            transform: rotate(15deg);
        }

        .mobile-link {
            color: var(--text-darker);
            text-decoration: none;
            font-size: 1.2rem;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border: 1px solid transparent;
            font-weight: 600;
        }

        .dark-mode .mobile-link {
            color: var(--text-dark);
        }

        .mobile-link:hover {
            color: var(--accent);
            background: rgba(52, 152, 219, 0.1);
            border-color: var(--accent);
            padding-right: 1.5rem;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Hero Section */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding-top: 80px;
        }

        .hero-content {
            text-align: center;
            max-width: 900px;
            z-index: 1;
            padding: 0 1rem;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            margin-bottom: 1.5rem;
            color: var(--text-darker);
            animation: fadeInUp 1s ease-out;
            line-height: 1.2;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .dark-mode .hero-title {
            color: var(--text-dark);
        }

        .hero-subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.4rem);
            margin-bottom: 2.5rem;
            color: var(--text-darker);
            animation: fadeInUp 1s ease-out 0.3s both;
            line-height: 1.6;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            font-weight: 600;
            opacity: 0.9;
        }

        .hero-cta {
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Professor Section */
        .professor-section {
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }

        .professor-container {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .professor-card {
            background: var(--card-light);
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-light);
            text-align: center;
            max-width: 500px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .dark-mode .professor-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            box-shadow: var(--shadow-dark);
        }

        .professor-card:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(45deg, var(--accent), var(--secondary));
        }

        .professor-image-container {
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
            position: relative;
        }

        .professor-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid transparent;
            background: linear-gradient(135deg, var(--accent), var(--secondary)) border-box;
            -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
        }

        .professor-image:hover {
            transform: scale(1.05);
        }

        .professor-info {
            text-align: center;
        }

        .professor-info h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--text-darker);
            font-weight: 700;
        }

        .dark-mode .professor-info h3 {
            color: var(--text-dark);
        }

        .professor-info p {
            color: var(--text-darker);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .dark-mode .professor-info p {
            color: var(--text-dark);
        }

        /* Features Section */
        .features {
            padding: 6rem 0;
            position: relative;
        }

        .section-title {
            text-align: center;
            font-size: clamp(2rem, 4vw, 2.8rem);
            margin-bottom: 1rem;
            position: relative;
            display: block;
            color: var(--text-darker);
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            padding: 0 1rem;
            font-weight: 800;
            width: 100%;
            left: 0;
            transform: none;
        }

        .dark-mode .section-title {
            color: var(--text-dark);
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(45deg, var(--accent), var(--secondary));
            border-radius: 3px;
        }

        .section-subtitle {
            text-align: center;
            font-size: clamp(1rem, 2vw, 1.2rem);
            margin-bottom: 3rem;
            color: var(--text-darker);
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            padding: 0 1rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .dark-mode .section-subtitle {
            color: var(--text-dark);
        }

        .grid {
            display: grid;
            gap: 2rem;
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .portfolio-card {
            background: var(--card-light);
            border-radius: 15px;
            padding: 2.5rem 2rem;
            transition: var(--transition);
            box-shadow: var(--shadow);
            height: 100%;
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
        }

        .portfolio-card:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(45deg, var(--accent), var(--secondary));
            transform: scaleX(0);
            transition: var(--transition);
        }

        .portfolio-card:hover:before {
            transform: scaleX(1);
        }

        .dark-mode .portfolio-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            box-shadow: var(--shadow-dark);
        }

        .portfolio-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .dark-mode .portfolio-card:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        .feature-card {
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            font-size: 2rem;
            color: white;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
            transition: var(--transition);
            animation: iconFloat 3s ease-in-out infinite;
        }

        .feature-card:hover .feature-icon {
            animation: iconPulse 0.5s ease-in-out;
            transform: scale(1.1);
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes iconPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1.1); }
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-darker);
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-weight: 700;
        }

        .dark-mode .feature-card h3 {
            color: var(--text-dark);
        }

        .feature-card p {
            color: var(--text-darker);
            line-height: 1.7;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-weight: 600;
            opacity: 0.9;
        }

        .dark-mode .feature-card p {
            color: var(--text-dark);
        }

        /* Locations Section */
        .locations-section {
            padding: 6rem 0;
        }

        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 768px) {
            .locations-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .locations-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .location-card {
            background: var(--card-light);
            border-radius: 15px;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow);
            height: 100%;
            border: 1px solid var(--border-light);
        }

        .dark-mode .location-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            box-shadow: var(--shadow-dark);
        }

        .location-card:hover {
            transform: translateY(-5px);
        }

        .location-map {
            height: 180px;
            width: 100%;
        }

        .location-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: calc(100% - 180px);
        }

        .location-info h3 {
            margin-bottom: 1rem;
            color: var(--text-darker);
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-weight: 700;
        }

        .dark-mode .location-info h3 {
            color: var(--text-dark);
        }

        .location-info p {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            word-wrap: break-word;
            overflow-wrap: break-word;
            color: var(--text-darker);
            font-weight: 600;
            opacity: 0.9;
        }

        .dark-mode .location-info p {
            color: var(--text-dark);
        }

        .location-info i {
            color: var(--accent);
            flex-shrink: 0;
        }

        .location-info .btn {
            margin-top: auto;
        }

        .main-map-card {
            padding: 2rem;
            background: var(--card-light);
            border-radius: 15px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-light);
        }

        .dark-mode .main-map-card {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            box-shadow: var(--shadow-dark);
        }

        #main-map {
            height: 500px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .dark-mode #main-map {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(52, 152, 219, 0.3);
            border-radius: 50%;
            border-top-color: var(--accent);
            animation: spin 1s ease-in-out infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Footer */
        footer {
            background: var(--card-light);
            padding: 4rem 0 2rem;
            text-align: center;
            border-top: 1px solid var(--border-light);
        }

        .dark-mode footer {
            background: var(--card-dark);
            border-top: 1px solid var(--border-dark);
        }

        .footer-content h3 {
            font-size: clamp(1.8rem, 3vw, 2.2rem);
            margin-bottom: 1.5rem;
            color: var(--text-darker);
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-weight: 700;
        }

        .dark-mode .footer-content h3 {
            color: var(--text-dark);
        }

        .footer-content p {
            margin-bottom: 2rem;
            color: var(--text-darker);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            word-wrap: break-word;
            overflow-wrap: break-word;
            padding: 0 1rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .dark-mode .footer-content p {
            color: var(--text-dark);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            font-size: 1.3rem;
            transition: var(--transition);
            animation: socialFloat 3s ease-in-out infinite;
        }

        .social-links a:nth-child(2) {
            animation-delay: 0.5s;
        }

        .social-links a:nth-child(3) {
            animation-delay: 1s;
        }

        .social-links a:hover {
            transform: translateY(-5px) rotate(10deg);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.5);
            animation: none;
        }

        @keyframes socialFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .footer-bottom {
            border-top: 1px solid var(--border-light);
            padding-top: 2rem;
            margin-top: 2rem;
            color: var(--text-darker);
            font-weight: 600;
            opacity: 0.9;
        }

        .dark-mode .footer-bottom {
            color: var(--text-dark);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-title {
                font-size: clamp(2.2rem, 4vw, 3rem);
            }
            
            .desktop-nav {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }

            .professor-card {
                margin: 0 1rem;
                padding: 2rem 1.5rem;
            }

            .professor-image-container {
                width: 150px;
                height: 150px;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: clamp(2rem, 5vw, 2.5rem);
            }
            
            .hero-subtitle {
                font-size: clamp(1rem, 3vw, 1.2rem);
            }
            
            .section-title {
                font-size: clamp(1.8rem, 4vw, 2.2rem);
            }
            
            .header-actions {
                display: none;
            }

            #main-map {
                height: 400px;
            }

            .portfolio-card, .location-card {
                padding: 1.5rem;
            }

            .professor-info h3 {
                font-size: 1.5rem;
            }

            .professor-info p {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                width: 95%;
                padding: 0 10px;
            }

            .hero-content, .section-title, .section-subtitle, .footer-content p {
                padding: 0 0.5rem;
            }

            .mobile-nav {
                width: 90%;
                padding: 1.5rem;
            }

            #main-map {
                height: 300px;
            }

            .professor-card {
                padding: 1.5rem 1rem;
            }

            .professor-image-container {
                width: 120px;
                height: 120px;
            }
        }

        /* Scroll Animations */
        .scroll-animate {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s, transform 0.8s;
        }

        .scroll-animate.animated {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Interactive Background -->
    <div class="interactive-bg">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </div>

    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <a href="#" class="logo">
                <i class="fas fa-atom"></i>
                Mr/ Mahmoud monier
            </a>
            <nav class="desktop-nav">
                <a href="#hero">الرئيسية</a>
                <a href="#features">المميزات</a>
                <a href="#locations">أماكن التواجد</a>
                <a href="#contact">اتصل بنا</a>
                <button class="theme-toggle-header" id="themeToggleHeader">
                    <i class="fas fa-moon"></i>
                </button>
            </nav>
            <div class="header-actions">
                 <?php if ($user): ?>
                    <a href="<?= is_admin() ? 'admin/index.php' : 'student/index.php' ?>" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> حساب جديد
                    </a>
                <?php endif; ?>
                <button class="theme-toggle-header" id="themeToggleHeaderDesktop">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
             <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

     <!-- Mobile Navigation -->
    <nav class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-header">
            <button class="mobile-theme-toggle" id="mobileThemeToggle">
                <i class="fas fa-moon"></i>
            </button>
            <button class="close-menu" id="closeMenu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <a href="#hero" class="mobile-link">
            <i class="fas fa-home"></i> الرئيسية
        </a>
        <a href="#features" class="mobile-link">
            <i class="fas fa-star"></i> المميزات
        </a>
        <a href="#locations" class="mobile-link">
            <i class="fas fa-map-marker-alt"></i> أماكن التواجد
        </a>
        <a href="#contact" class="mobile-link">
            <i class="fas fa-phone"></i> اتصل بنا
        </a>
        <hr>
        <?php if ($user): ?>
            <a href="<?= is_admin() ? 'admin/index.php' : 'student/index.php' ?>" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
            </a>
            <a href="register.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> حساب جديد
            </a>
        <?php endif; ?>
    </nav>
    <div class="overlay" id="overlay"></div>

    <!-- Hero Section -->
    <section class="hero" id="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">إتقان الكيمياء أصبح بين يديك</h1>
                <p class="hero-subtitle">انضم لمنصتنا التفاعلية واستكشف عالم الكيمياء بطريقة لم تختبرها من قبل. شرح مبتكر، تجارب افتراضية، ومتابعة فورية.</p>
                <div class="hero-cta">
                    <a href="register.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-rocket"></i> ابدأ رحلتك الآن
                    </a>
                </div>
            </div>
        </div>
    </section>

     <!-- Professor Section -->
    <section class="professor-section">
        <div class="container">
            <div class="professor-container">
                <div class="professor-card scroll-animate">
                    <div class="professor-image-container">
                        <img src="assets/images/professor.png" alt="الأستاذ محمود منير" class="professor-image">
                    </div>
                    <div class="professor-info">
                        <h3>الأستاذ/ محمود منير</h3>
                        <p>مدرس الكيمياء المتخصص - خبرة أكثر من 15 عاماً</p>
                        <div class="hero-cta">
                            <a href="register.php" class="btn btn-primary">
                                <i class="fas fa-graduation-cap"></i> انضم إلى الدروس
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title scroll-animate">لماذا نحن خيارك الأول؟</h2>
            <p class="section-subtitle scroll-animate">نقدم لك تجربة تعليمية فريدة تجمع بين التميز الأكاديمي والتقنية الحديثة</p>
            <div class="grid grid-3">
                <div class="feature-card portfolio-card scroll-animate">
                    <div class="feature-icon"><i class="fas fa-video"></i></div>
                    <h3>محاضرات عالية الجودة</h3>
                    <p>شرح فيديو تفصيلي لكل أجزاء المنهج بأحدث تقنيات التصوير والمونتاج لضمان أفضل تجربة تعليمية.</p>
                </div>
                <div class="feature-card portfolio-card scroll-animate">
                     <div class="feature-icon"><i class="fas fa-vial-virus"></i></div>
                    <h3>معامل افتراضية</h3>
                    <p>قم بإجراء التجارب الكيميائية الخطرة والمعقدة بأمان تام عبر معاملنا الافتراضية التفاعلية.</p>
                </div>
                <div class="feature-card portfolio-card scroll-animate">
                     <div class="feature-icon"><i class="fas fa-tasks"></i></div>
                    <h3>اختبارات ومتابعة</h3>
                    <p>بنك أسئلة ضخم واختبارات دورية لتحديد مستواك ومتابعة تقدمك الدراسي خطوة بخطوة.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Locations Section -->
    <section class="locations-section" id="locations">
        <div class="container">
            <h2 class="section-title scroll-animate">أماكن تواجدنا</h2>
            <p class="section-subtitle scroll-animate">يمكنك الحضور شخصيًا في أحد مقراتنا للحصول على الدعم والمراجعات النهائية</p>
            <div class="locations-grid" id="locations-container">
                <!-- Locations will be loaded dynamically via JavaScript -->
                <div class="loader"></div>
            </div>
             <div class="main-map-card scroll-animate" style="margin-top: 3rem;">
                <h3 style="text-align: center; margin-bottom: 1.5rem; color: var(--text-darker);">خريطة تفاعلية لجميع الأفرع</h3>
                <div id="main-map"></div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <h3 class="scroll-animate">تواصل معنا</h3>
                <p class="scroll-animate">للاستفسارات والحجز، يمكنك التواصل معنا عبر الوسائل التالية:</p>
                <div class="social-links scroll-animate">
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
                <div class="footer-bottom">
                    <p>جميع الحقوق محفوظة &copy; <?= date('Y') ?> لمنصة الكيمياء التعليمية</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // JavaScript محسن مع تأثيرات إضافية
        document.addEventListener('DOMContentLoaded', function() {
            
            // Dark/Light Mode Toggle
            const themeToggleHeader = document.getElementById('themeToggleHeader');
            const themeToggleDesktop = document.getElementById('themeToggleHeaderDesktop');
            const mobileThemeToggle = document.getElementById('mobileThemeToggle');
            const themeIcons = document.querySelectorAll('.theme-toggle-header i, .mobile-theme-toggle i');
            
            // Check for saved theme preference or default to light
            const currentTheme = localStorage.getItem('theme') || 'light';
            if (currentTheme === 'dark') {
                document.documentElement.classList.add('dark-mode');
                themeIcons.forEach(icon => {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                });
            }
            
            function toggleTheme() {
                document.documentElement.classList.toggle('dark-mode');
                
                if (document.documentElement.classList.contains('dark-mode')) {
                    themeIcons.forEach(icon => {
                        icon.classList.remove('fa-moon');
                        icon.classList.add('fa-sun');
                    });
                    localStorage.setItem('theme', 'dark');
                } else {
                    themeIcons.forEach(icon => {
                        icon.classList.remove('fa-sun');
                        icon.classList.add('fa-moon');
                    });
                    localStorage.setItem('theme', 'light');
                }
            }
            
            themeToggleHeader.addEventListener('click', toggleTheme);
            themeToggleDesktop.addEventListener('click', toggleTheme);
            mobileThemeToggle.addEventListener('click', toggleTheme);
            
            // 1. Sticky Header
            const header = document.querySelector('.main-header');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });

            // 2. Mobile Menu
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const closeMenuBtn = document.getElementById('closeMenu');
            const mobileNav = document.getElementById('mobileNav');
            const overlay = document.getElementById('overlay');
            const mobileLinks = document.querySelectorAll('.mobile-link');
            
            const openMenu = () => {
                mobileNav.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            };
            
            const closeMenu = () => {
                mobileNav.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            };

            mobileMenuBtn.addEventListener('click', openMenu);
            closeMenuBtn.addEventListener('click', closeMenu);
            overlay.addEventListener('click', closeMenu);
            mobileLinks.forEach(link => link.addEventListener('click', closeMenu));
            
            // 3. Scroll Animations
            const scrollElements = document.querySelectorAll('.scroll-animate');
            
            const elementInView = (el, dividend = 1) => {
                const elementTop = el.getBoundingClientRect().top;
                return (
                    elementTop <= (window.innerHeight || document.documentElement.clientHeight) / dividend
                );
            };
            
            const displayScrollElement = (element) => {
                element.classList.add('animated');
            };
            
            const handleScrollAnimation = () => {
                scrollElements.forEach((el) => {
                    if (elementInView(el, 1.2)) {
                        displayScrollElement(el);
                    }
                });
            };
            
            window.addEventListener('scroll', () => {
                handleScrollAnimation();
            });
            
            // Trigger once on load
            handleScrollAnimation();








            
           // 4. Locations and Maps
    const locationsContainer = document.getElementById('locations-container');
    if(locationsContainer){
        // Initialize main map (centered on Egypt)
        const mainMap = L.map('main-map').setView([26.8206, 30.8025], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(mainMap);

        // Fetch locations data from server
        fetch('api/get_locations.php') // You need to create this API endpoint
            .then(response => response.json())
            .then(locations => {
                if (locations.length === 0) {
                     locationsContainer.innerHTML = '<p>لا توجد أماكن متاحة حالياً.</p>';
                     return;
                }
                locationsContainer.innerHTML = ''; // Clear loader
                
                locations.forEach(location => {
                    // Create Location Card
                    const card = document.createElement('div');
                    card.className = 'location-card';
                    card.innerHTML = `
                        <div class="location-map" id="map-${location.id}"></div>
                        <div class="location-info">
                            <h3>${location.name}</h3>
                            <p><i class="fas fa-map-marker-alt"></i> ${location.address}</p>
                            <p><i class="fas fa-clock"></i> ${location.working_hours}</p>
                            <p><i class="fas fa-phone"></i> ${location.phone}</p>
                            <a href="https://www.google.com/maps?q=${location.latitude},${location.longitude}" target="_blank" class="btn btn-primary" style="margin-top: auto;">عرض على الخريطة</a>
                        </div>
                    `;
                    locationsContainer.appendChild(card);
                    
                    // Create mini map inside the card
                    const miniMap = L.map(`map-${location.id}`, {
                        zoomControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false
                    }).setView([location.latitude, location.longitude], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
                    L.marker([location.latitude, location.longitude]).addTo(miniMap);

                    // Add marker to the main map
                    const mainMarker = L.marker([location.latitude, location.longitude]).addTo(mainMap);
                    mainMarker.bindPopup(`<b>${location.name}</b><br>${location.address}`);
                });
            })
            .catch(error => {
                locationsContainer.innerHTML = '<p>حدث خطأ أثناء تحميل أماكن التواجد.</p>';
                console.error('Error fetching locations:', error);
            });
    }
});
    </script>
</body>
</html>