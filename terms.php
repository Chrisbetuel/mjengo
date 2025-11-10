<?php
require_once 'config.php';

// Get current language
$lang = getCurrentLanguage();
$translations = loadLanguage($lang);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['terms_and_conditions']; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a5276;
            --secondary: #f39c12;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #27ae60;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        .terms-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .terms-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--success);
        }

        .terms-title {
            color: var(--primary);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .terms-subtitle {
            color: var(--dark);
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .terms-content {
            margin-bottom: 2rem;
        }

        .terms-section {
            margin-bottom: 2rem;
        }

        .terms-section h3 {
            color: var(--success);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .terms-section p {
            margin-bottom: 1rem;
            text-align: justify;
        }

        .terms-section ul {
            margin-bottom: 1rem;
            padding-left: 2rem;
        }

        .terms-section li {
            margin-bottom: 0.5rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, var(--success), #2ecc71);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
            color: white;
            text-decoration: none;
        }

        .back-btn i {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .terms-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .terms-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="terms-container">
            <div class="terms-header">
                <h1 class="terms-title"><?php echo $translations['terms_and_conditions']; ?></h1>
                <p class="terms-subtitle"><?php echo SITE_NAME; ?> - <?php echo $lang === 'sw' ? 'Sheria na Masharti za Matumizi' : 'Terms and Conditions of Use'; ?></p>
            </div>

            <div class="terms-content">
                <?php if ($lang === 'sw'): ?>
                    <!-- Swahili Version -->
                    <div class="terms-section">
                        <h3>1. Utangulizi</h3>
                        <p>Karibu kwenye <?php echo SITE_NAME; ?>. Sheria na masharti haya yanafafanua sheria za matumizi ya tovuti yetu na huduma zetu. Kwa kutumia tovuti hii, unakubali kufuata sheria na masharti hizi.</p>
                    </div>

                    <div class="terms-section">
                        <h3>2. Matumizi ya Huduma</h3>
                        <p>Huduma zetu zinakusudiwa kusaidia watumiaji kupata vifaa vya ujenzi kupitia michuano ya malipo ya kila siku na mipango mingine ya malipo. Unakubali kutumia huduma hizi kwa madhumuni halali tu.</p>
                    </div>

                    <div class="terms-section">
                        <h3>3. Usajili na Akaunti</h3>
                        <p>Ili kutumia huduma zetu kamili, lazima ujisajili akaunti. Taarifa zote unazotoa lazima ziwe sahihi na za sasa. Wewe ndiye mwajibikaji wa kulinda usiri wa akaunti yako.</p>
                    </div>

                    <div class="terms-section">
                        <h3>4. Malipo na Malipo</h3>
                        <p>Malipo yanafanywa kupitia njia salama za malipo. Unakubali kulipa ada zote zinazohusiana na huduma unazochagua. Malipo ni ya mwisho na hayatolewi.</p>
                    </div>

                    <div class="terms-section">
                        <h3>5. Utoaji wa Vifaa</h3>
                        <p>Vifaa vya ujenzi hutolewa kulingana na mpango wa malipo uliochagua. Tunajitahidi kutoa vifaa kwa wakati, lakini hatuhakikishi utoaji wa wakati maalum.</p>
                    </div>

                    <div class="terms-section">
                        <h3>6. Kufuta na Kurejesha</h3>
                        <p>Unaweza kufuta akaunti yako wakati wowote. Baada ya kufuta, taarifa zako zote zitafutwa. Hakuna kurejesha kwa malipo yaliyofanywa.</p>
                    </div>

                    <div class="terms-section">
                        <h3>7. Ubora wa Vifaa</h3>
                        <p>Tunatoa vifaa vya ubora wa juu kutoka kwa wasambazaji wa kuaminika. Hata hivyo, hatuhakikishi ubora wa vifaa baada ya utoaji.</p>
                    </div>

                    <div class="terms-section">
                        <h3>8. Ulinzi wa Taarifa</h3>
                        <p>Tunazingatia sana ulinzi wa taarifa zako binafsi. Taarifa zako hutumiwa tu kwa madhumuni ya kutoa huduma zetu na hazitashirikiwa na wahusika wengine bila idhini yako.</p>
                    </div>

                    <div class="terms-section">
                        <h3>9. Marekebisho ya Sheria na Masharti</h3>
                        <p>Tuna haki ya kurekebisha sheria na masharti haya wakati wowote. Marekebisho yatatangazwa kwenye tovuti yetu.</p>
                    </div>

                    <div class="terms-section">
                        <h3>10. Mawasiliano</h3>
                        <p>Kwa maswali yoyote kuhusu sheria na masharti hizi, tafadhali wasiliana nasi kupitia anwani ya barua pepe au nambari ya simu iliyotolewa kwenye tovuti yetu.</p>
                    </div>
                <?php else: ?>
                    <!-- English Version -->
                    <div class="terms-section">
                        <h3>1. Introduction</h3>
                        <p>Welcome to <?php echo SITE_NAME; ?>. These terms and conditions outline the rules for the use of our website and services. By using this website, you agree to comply with these terms and conditions.</p>
                    </div>

                    <div class="terms-section">
                        <h3>2. Use of Services</h3>
                        <p>Our services are intended to help users acquire building materials through daily payment challenges and other payment plans. You agree to use these services for lawful purposes only.</p>
                    </div>

                    <div class="terms-section">
                        <h3>3. Registration and Account</h3>
                        <p>To use our full services, you must register an account. All information you provide must be accurate and current. You are responsible for maintaining the confidentiality of your account.</p>
                    </div>

                    <div class="terms-section">
                        <h3>4. Payments and Billing</h3>
                        <p>Payments are made through secure payment methods. You agree to pay all fees associated with the services you choose. Payments are final and non-refundable.</p>
                    </div>

                    <div class="terms-section">
                        <h3>5. Material Delivery</h3>
                        <p>Building materials are delivered according to the payment plan you choose. We strive to deliver materials on time, but we do not guarantee delivery at specific times.</p>
                    </div>

                    <div class="terms-section">
                        <h3>6. Cancellation and Refunds</h3>
                        <p>You can delete your account at any time. After deletion, all your information will be removed. There are no refunds for payments made.</p>
                    </div>

                    <div class="terms-section">
                        <h3>7. Material Quality</h3>
                        <p>We provide high-quality materials from trusted suppliers. However, we do not guarantee the quality of materials after delivery.</p>
                    </div>

                    <div class="terms-section">
                        <h3>8. Privacy Protection</h3>
                        <p>We take the protection of your personal information very seriously. Your information is used only to provide our services and will not be shared with third parties without your consent.</p>
                    </div>

                    <div class="terms-section">
                        <h3>9. Amendments to Terms and Conditions</h3>
                        <p>We reserve the right to amend these terms and conditions at any time. Amendments will be announced on our website.</p>
                    </div>

                    <div class="terms-section">
                        <h3>10. Contact</h3>
                        <p>For any questions regarding these terms and conditions, please contact us via the email address or phone number provided on our website.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center">
                <a href="register.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    <?php echo $lang === 'sw' ? 'Rudi kwenye Usajili' : 'Back to Registration'; ?>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
