<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A1 Motors - Footer</title>
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
        background: var(--primary, #1a1a2e);
        color: white;
        padding: 40px 20px;
        margin-top: auto;
    }

    .footer-container { 
        max-width: 1200px; 
        margin: 0 auto; 
    }

    .footer-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
        gap: 20px; 
        text-align: center;
    }

    h3 { 
        font-size: 18px; 
        margin-bottom: 10px; 
    }
    
    .description { 
        color: #ccc; 
        margin-top: 10px; 
    }
    
    ul { 
        list-style: none; 
    }
    
    ul li { 
        margin-bottom: 8px; 
        color: #ccc; 
    }
    
    .footer-bottom { 
        text-align: center; 
        margin-top: 20px; 
        color: #ccc; 
    }
    
    .whatsapp-btn {
        display: inline-block;
        background: #25D366;
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        margin-top: 10px;
        transition: background 0.3s;
    }
    
    .whatsapp-btn:hover {
        background: #128C7E;
    }
    
    .logo {
        max-width: 150px;
        height: auto;
        margin-bottom: 15px;
    }

    .orariPunes h3, .kontakti h3 {
        color: var(--text-light);
    }

    #email:link {
        color: var(--text-light);
    }

    #email:visited {
        color: var(--gray);
    }
    
    .adresa {
        color: var(--text-light);
    }

    .numri a,
    .numri a:link,
    .numri a:visited,
    .numri a:hover,
    .numri a:active {
        color: rgb(31, 221, 255);
        text-decoration: none;
    }
</style>
<body>
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div>
                    <!-- A1 Rent A Car Logo -->
                    <!-- <img src="https://via.placeholder.com/150x50?text=A1+Motors" class="logo" alt="A1 Rent A Car Logo"> -->
                    <p class="description">A1 Rent A Car ofron shërbime të cilësisë së lartë të qirasë së automjeteve me çmime konkurruese dhe shërbim të personalizuar.</p>
                </div>
                <div class="orariPunes">
                    <h3>Orari i Punës: 24/7</h3>
                </div>
                <div class="kontakti">
                    <h3>Na Kontaktoni</h3>
                    <ul>
                        <li class="adresa">📍 Prishtina International Airport “Adem Jashari”</li>
                        <li class="numri"><a href="tel:+38348204402">📞 +383 48 204 402</a></li>
                        <li>📧 <a id="email" href="mailto:a1rentacar01@gmail.com">a1rentacar01@gmail.com</a></li>
                        <li>
                            <a href="https://wa.me/38348204402" class="whatsapp-btn" target="_blank">
                                📱 Na kontaktoni në WhatsApp
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 A1 Rent A Car. Të gjitha të drejtat e rezervuara.</p>
            </div>
        </div>
    </footer>
</body>
</html>
<?php ob_end_flush(); ?>