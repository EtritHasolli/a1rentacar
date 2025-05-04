<?php
    ob_start();
    session_start();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    include_once "functions/functions.php";
    include_once "functions/db.php";

    $isAdmin = isset($_SESSION['user_id']) && isAdmin($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <title>A1 Rent a Car</title>
    <link rel = "icon" type = "image/png" href = "images/tabLogo.png">
</head>
<style>
    :root {
        --primary: #1a1a2e;
        --primary-dark: #16213e;
        --secondary: #2C3E50;
        --background-color: #F8F9FA;
        --text-dark: #333;
        --text-light: #fff;
        --text-muted: #666;
        --text-white: #ffffff;
        --white: #ffffff;
        --shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        --hover-bg: #d8d8f0;
        --avatar-bg-color: #e9ecef;
        --light: #F8F9FA;
        --dark: #333333;
        --gray: #666666;
        --light-gray: #E0E0E0;
        --white: #FFFFFF;
        --black: #000000;
        --shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        --border: 2px solid #1a1a2e;
        --border-slim: 1px solid white;
        --border-slim-dark: 1px solid #1a1a2e;
        --transition: all 0.3s ease;
        --background-color-btn: #1a1a2e;
        --background-color-btn-hover: #2e2e4d;
        --header-btn-color: #f5f5f0;
        --header-btn-hover:rgb(62, 62, 103);
        --btn-text-color: #fff;
        --btn-border-color: #4e4e7c;
        --background-color-delete-btn: #ff4f4f;
        --background-color-delete-btn-hover: #ff1f1f;
        --calendar-td-hover: #16213e;
        --ivory: #f5f5f0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--background-color);
        color: var(--text-dark);
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    .header-navbar {
        background-color: var(--primary);
        width: 100%;
        box-shadow: var(--shadow);
        position: reltaive;
        top: 0;
        z-index: 1000;
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1500px;
        padding: 15px 5px;
    }

    .brand {
        display: flex;
        flex-direction: row;
        justify-content: center;
        padding: 0 10px;
    }

    .brand-name {
        display:flex;
        flex-direction: row;
        align-items: center;
        gap: 10px;
        color: var(--text-light);
        font-size: 23px;
        font-weight: bold;
        letter-spacing: 1px;
    }

    .menu {
        display: flex;
        flex-direction: row;
        align-self: flex-end;
    }

    .menu ul {
        list-style: none;
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 15px;
        padding: 0;
        margin-left: -100px;
    }

    .menu ul li a {
        color: var(--text-dark);
        text-decoration: none;
        font-size: 16px;
        font-weight: 500;
        padding: 10px 15px;
        border-radius: 20px;
        transition: var(--transition);
        background: var(--header-btn-color);
    }

    .menu ul li a:hover {
        color: var(--text-light);
        background: var(--header-btn-hover);
    }

    .menu-icon {
        display: none;
        cursor: pointer;
        font-size: 24px;
        color: white;
        padding: 10px;
    }

    .mobile-menu {
        display: none;
        background: var(--primary-dark);
        text-align: center;
        padding: 10px 0;
        width: 100%;
    }

    .mobile-menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .mobile-menu ul li {
        padding: 12px 0;
    }

    .mobile-menu ul li a {
        color: var(--text-light);
        text-decoration: none;
        font-size: 16px;
        padding: 10px 20px;
        display: block;
        transition: var(--transition);
    }

    .mobile-menu ul li a:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .auth-menu {
        cursor: pointer;
        font-size: 24px;
        color: white;
        padding: 8px 12px;
        border-radius: 5px;
        transition: var(--transition);
    }

    .auth-menu:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    #auth-toggle {
        display: none;
    }

    .auth-dropdown {
        position: absolute;
        right: 20px;
        top: 60px;
        background: white;
        border-radius: 8px;
        padding: 10px 0;
        display: none;
        box-shadow: var(--shadow);
        z-index: 100;
        min-width: 180px;
    }

    #auth-toggle:checked + .auth-dropdown {
        display: block;
    }

    .auth-dropdown a {
        display: block;
        color: var(--text-dark);
        text-decoration: none;
        padding: 10px 20px;
        transition: var(--transition);
    }

    .auth-dropdown a:hover {
        background: var(--hover-bg);
    }

    .auth-dropdown::before {
        content: '';
        position: absolute;
        top: -8px;
        right: 15px;
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-bottom: 8px solid white;
    }

    @media (min-width: 1025px) {
        .mobile-menu {
            display: none;
        }

        .mobile-menu.active {
            display: none;
        }
    }

    @media (max-width: 1024px) {
        .menu {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .menu-icon {
            display: block;
        }
        
        .auth-dropdown {
            right: 10px;
            top: 50px;
        }

        .mobile-menu.active {
            display: block;
        }

        .brand {
            justify-content: center;
            padding: 0 10px;
        }
    }
    
</style>
<body>
    <nav class="header-navbar">
        <div class="header-container">
            <div class="brand">
                <div class="brand-name"><img src="images/logo1.png?v=1" alt="Logo e kompanise" style="width: 50px; height: auto;"> <span>Rent A Car</span></div>
            </div>
            
            <div class="menu">
                <ul>
                    <li><a href="index.php" class="active">Home</a></li>
                    <?php if(isset($_SESSION['user_id'])) { ?>
                    <li><a href="car-list.php">Makinat</a></li>
                    <?php }?>
                </ul>
            </div>
            
            <div class="menu-cars">
                <label class="auth-menu" for="auth-toggle" onclick="toggleMenu()">☰</label>
                <input type="checkbox" id="auth-toggle">
                <div class="auth-dropdown" id="auth-dropdown">
                    <?php if(!isset($_SESSION['user_id'])) { ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <?php } else { ?>
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="functions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    <?php } ?>
                </div>
            </div>
        </div>
        
        <div id="mobile-menu" class="mobile-menu hidden">
            <ul>
                <li><a href="index.php">Ballina</a></li>
                <?php if(!isset($_SESSION['user_id'])) { ?>
                <li><a href="login.php">Login</a></li>
                <?php } else { ?>
                <li><a href="car-list.php">Lista e Makinave</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="functions/logout.php">Logout</a></li>
                <?php } ?>
            </ul>
        </div>
    </nav>
</body>
<script>
    function toggleMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const dropdown = document.getElementById('auth-dropdown');
        if (window.innerWidth <= 1024) {
            if (mobileMenu.style.display == 'block') {
                mobileMenu.style.display = 'none';
            } else {
                mobileMenu.style.display = 'block';
                dropdown.style.display = 'none';
            }
        }   else if (window.innerWidth >= 1025){
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.style.display = 'none';
            if (dropdown.style.display == 'block') {
                dropdown.style.display = 'none';
            } else {
                dropdown.style.display = 'block';
            }
        } 
    }
</script>
</html>
