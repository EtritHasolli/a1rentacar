<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>
<style>
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; 
    }
    
    html, body {
        height: 100%;
        margin: 0;
        display: flex;
        flex-direction: column;
    }

    section {
        flex: 1;
    }

    .footer {
        background: linear-gradient(135deg, #1a1a2e 60%, #16213e 100%);
        color: #fff;
        padding: 48px 20px 24px 20px;
        margin-top: auto;
        border-top: 4px solid #25D366;
        box-shadow: 0 -2px 24px rgba(0,0,0,0.08);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .footer-container { 
        max-width: 1300px; 
        margin: 0 auto; 
    }

    .footer-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
        gap: 32px; 
        text-align: left;
        align-items: flex-start;
    }

    .footer .logo {
        max-width: 140px;
        height: auto;
        margin-bottom: 18px;
        display: block;
    }

    .footer h3 { 
        font-size: 1.15rem; 
        margin-bottom: 12px; 
        color: #25D366;
        letter-spacing: 1px;
    }
    
    .footer .description { 
        color: #e0e0e0; 
        font-size: 1rem; 
        margin-bottom: 10px; 
        line-height: 1.6;
    }
    
    .footer ul { 
        list-style: none; 
        padding: 0;
        margin: 0;
    }
    
    .footer ul li { 
        margin-bottom: 10px; 
        color: #e0e0e0; 
        font-size: 1rem; 
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .footer a {
        color: #25D366;
        text-decoration: none;
        transition: color 0.2s;
        font-weight: 500;
    }
    
    .footer a:hover {
        color: #fff;
        text-decoration: underline;
    }
    
    .footer .whatsapp-btn {
        display: inline-block;
        background: #25D366;
        color: #fff;
        padding: 10px 20px;
        border-radius: 25px;
        text-decoration: none;
        margin-top: 8px;
        font-weight: bold;
        font-size: 1rem;
        transition: background 0.3s, color 0.3s;
        box-shadow: 0 2px 8px rgba(37,211,102,0.08);
    }
    
    .footer .whatsapp-btn:hover {
        background: #128C7E;
        color: #fff;
    }
    
    .footer-bottom { 
        text-align: center; 
        margin-top: 32px; 
        color: #bdbdbd; 
        font-size: 0.98rem; 
        letter-spacing: 0.5px;
    }
    
    @media (max-width: 600px) {
        .footer {
            padding: 32px 8px 16px 8px;
        }
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 20px;
            text-align: center;
        }
        .footer .logo {
            margin: 0 auto 16px auto;
        }

        .orariPunes {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
    }
</style>
<body>
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div>
                    <!-- Uncomment and update the src to use your real logo -->
                    <!-- <img src="images/logo1.png?v=1" class="logo" alt="A1 Rent A Car Logo"> -->
                    <p class="description">A1 Rent A Car offers high-quality car rental services with competitive prices and personalized service.</p>
                </div>
                <div class="orariPunes">
                    <h3>Working Hours</h3>
                    <ul><li>24/7</li></ul>
                </div>
                <div class="kontakti">
                    <h3>Contact us</h3>
                    <ul>
                        <li>📍 Prishtina International Airport “Adem Jashari”</li>
                        <li>📞 <a href="tel:+38348204402">+383 48 204 402</a></li>
                        <li>📧 <a href="mailto:a1rentacar01@gmail.com">a1rentacar01@gmail.com</a></li>
                        <li>
                            <a href="https://wa.me/38348204402" class="whatsapp-btn" target="_blank">
                                📱 Contact us on WhatsApp
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 A1 Rent A Car. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
<?php ob_end_flush(); ?>