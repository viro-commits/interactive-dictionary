<?php
require 'dbconnect.php';
$error_jmeno = "";

$citaty = ["Každé nové slovo je nový svět.", "Opakování je matka moudrosti.", "Investice do vědění nese nejlepší úrok.", "Kořeny vzdělání jsou hořké, ale ovoce je sladké.", "Pro život, ne pro školu se učíme.", "Nejlepší část vzdělání je ta, kterou člověk získal sám."];
$nahodny_citat = $citaty[array_rand($citaty)];

// REGISTRACE - ZPRACOVÁNÍ FORMULÁŘE A VALIDACE 
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $uzivatel = mysqli_real_escape_string($conn, $_POST["username"]);
    $heslo1 = $_POST["password"];
    $heslo2 = $_POST["password2"];
    $email = mysqli_real_escape_string($conn, $_POST["email"]);

    $error_jmeno = "";
    $error_email = "";
    $error_heslo = "";
    $je_validni = true;

    // VALIDACE 1 - UŽIVATELSKÉ JMÉNO
    $allowed_chars = "abcdefghijklmnopqrstuvwxyzěščřžýáíéůúABCDEFGHIJKLMNOPQRSTUVWXYZĚŠČŘŽÝÁÍÉŮÚ0123456789";
    foreach(mb_str_split($uzivatel) as $znak){
        if(!str_contains($allowed_chars, $znak)){
            $error_jmeno .= "Nepovolený znak: " . $znak . ". ";
            $je_validni = false;
        }
    }

    if(mb_strlen($uzivatel) < 2){
        $error_jmeno .= "Jméno musí mít alespoň 2 písmena.";
        $je_validni = false;
    }

    // VALIDACE 2 - EMAIL
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error_email = "Email není validní.";
        $je_validni = false;
    }

    // VALIDACE 3 - HESLO A SHODA HESEL
    if(strlen($heslo1) < 8 or strlen($heslo1) > 32){
        $error_heslo = "Heslo musí mít 8–32 znaků.";
        $je_validni = false;
    }

    $obsahuje_male = false;
    $obsahuje_velke = false;
    $obsahuje_cislo = false;

    foreach(mb_str_split($heslo1) as $znak){
        if(ctype_lower($znak)){
            $obsahuje_male = true;
        }
        if(ctype_upper($znak)){
            $obsahuje_velke = true;
        }
        if(ctype_digit($znak)){
            $obsahuje_cislo = true;
        }
    }

    if(!$obsahuje_male or !$obsahuje_velke or !$obsahuje_cislo) {
        $error_heslo = "Heslo musí obsahovat aspoň 1 malé, 1 velké písmeno a 1 číslici.";
        $je_validni = false;
    }

    if($heslo1 != $heslo2){
        $error_heslo = "Zadaná hesla se neshodují.";
        $je_validni = false;
    }

    if($je_validni == true){
        // VALIDACE 4 - KONTROLA DUPLICITY
        $sql = "SELECT id FROM users WHERE username = '$uzivatel'";
        $kontrola = mysqli_query($conn, $sql);

        if(mysqli_num_rows($kontrola) > 0){
            $error_jmeno = "Uživatel s tímto jménem již existuje, zvolte prosím jiné.";
            $je_validni = false;
        }

        $sql = "SELECT id FROM users WHERE email = '$email'";
        $kontrola_email = mysqli_query($conn, $sql);

        if(mysqli_num_rows($kontrola_email) > 0){
            $error_email = "Tento email je již zaregistrován.";
            $je_validni = false;
        }

        if($je_validni == true){
            // ULOŽENÍ DO DATABÁZE
            $hash_heslo = password_hash($heslo1, PASSWORD_DEFAULT); // Heslo se ukládá jako hash
            $hash_heslo_db = mysqli_real_escape_string($conn, $hash_heslo);
            $sql = "INSERT INTO users (username, password, email) 
                    VALUES ('$uzivatel', '$hash_heslo_db', '$email')";
            if(mysqli_query($conn, $sql)){
                header("Location: index.php");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registrace</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after{
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body{
            background-color: #e2e0db;
            color: #1c1c1c;
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .wrapper{
            width: 100%;
            max-width: 780px;
        }

        /* HEADER */
        .site-header{
            margin-bottom: 24px;
        }

        .site-header h1{
            font-size: 2rem;
            font-weight: 800;
            color: #1c1c1c;
            letter-spacing: -0.5px;
        }

        .site-header h1 span{
            color: #cc1100;
        }

        .site-header .tagline{
            margin-top: 4px;
            font-size: 0.88rem;
            color: #888;
        }

        /* HLAVNÍ GRID */
        .page-grid{
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-areas:
                "panel form"
                "footer footer";
            gap: 16px;
            align-items: stretch;
        }

        /* LEVÝ PANEL */
        .panel{
            grid-area: panel;
            background: #eeece8;
            border-radius: 12px;
            padding: 36px 32px;
            border: 1px solid #d0cdc7;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .panel .vitej{
            font-size: 1.2rem;
            font-weight: 600;
            color: #1c1c1c;
            margin-bottom: 12px;
        }

        .panel .about-block{
            font-size: 0.95rem;
            color: #444;
            line-height: 1.75;
        }

        .panel .quote-block{
            border-left: 3px solid #cc1100;
            padding-left: 14px;
            margin-top: 28px;
        }

        .panel .quote-block p{
            font-size: 0.86rem;
            color: #888;
            font-style: italic;
            line-height: 1.6;
        }

        .panel .nazev-block{
            margin-top: auto;
            padding-top: 20px;
            font-size: 0.78rem;
            color: #aaa;
        }

        /* FORMULÁŘ */
        .form-box{
            grid-area: form;
            background: #eeece8;
            border-radius: 12px;
            padding: 36px 32px;
            border: 1px solid #d0cdc7;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .form-box h2{
            font-size: 1.1rem;
            font-weight: 700;
            color: #1c1c1c;
            margin-bottom: 4px;
        }

        .form-box label{
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #666;
        }

        .form-box input[type="text"], .form-box input[type="email"], .form-box input[type="password"]{
            padding: 10px 14px;
            background-color: #e2e0db;
            border: 1px solid #d0cdc7;
            border-radius: 10px;
            color: #1c1c1c;
            font-family: 'Roboto', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-box input:focus {
            border-color: #cc1100;
        }

        .form-box input::placeholder {
            color: #bbb;
        }

        .btn-submit {
            margin-top: 4px;
            padding: 12px;
            background-color: #cc1100;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.15s ease;
        }

        .btn-submit:hover{
            background-color: #aa0e00;
        }

        .link-back{
            font-size: 0.82rem;
            color: #aaa;
            text-decoration: none;
        }

        .link-back:hover{
            color: #1c1c1c;
        }

        .link-prihlaseni{
            color: #cc1100;
            text-decoration: none;
            font-weight: 500;
        }

        .link-prihlaseni:hover{
            text-decoration: underline;
        }

        /* ERROR HLÁŠKY */
        .error{
            color: #aa0e00;
            font-size: 0.82rem;
            font-weight: 600;
            background-color: #f5ede9;
            border: 1px solid #e8c8c3;
            border-radius: 8px;
            padding: 8px 12px;
        }

        /* PATIČKA */
        .footer{
            grid-area: footer;
            margin-top: 4px;
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #999;
        }

        /* RESPONSIVE */
        @media (max-width: 560px){
            .page-grid {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "panel"
                    "form"
                    "footer";
            }
        }
    </style>
</head>
<body>
<div class="wrapper">

    <header class="site-header">
        <h1>Slovníček<span>.</span></h1>
        <p class="tagline">Tvůj osobní interaktivní slovník</p>
    </header>

    <div class="page-grid">

        <!-- LEVÝ PANEL -->
        <div class="panel">
            <div>
                <p class="vitej">Registrace</p>
                <p class="about-block">Vytvoř si účet a začni se učit slovíčka vlastním tempem.</p>

                <div class="quote-block">
                    <p><?php echo htmlspecialchars($nahodny_citat); ?></p>
                </div>
            </div>

            <div class="nazev-block">Interaktivní Slovníček</div>
        </div>

        <!-- FORMULÁŘ -->
        <div class="form-box">
            <h2>Nový účet</h2>

            <?php if($error_jmeno != ""){ ?>
                <p class="error"><?php echo htmlspecialchars($error_jmeno); ?></p>
            <?php } ?>

            <?php if(isset($error_email) && $error_email != ""){ ?>
                <p class="error"><?php echo htmlspecialchars($error_email); ?></p>
            <?php } ?>

            <?php if(isset($error_heslo) && $error_heslo != ""){ ?>
                <p class="error"><?php echo htmlspecialchars($error_heslo); ?></p>
            <?php } ?>

            <p>Máš účet? <a href="prihlaseni.php" class="link-prihlaseni">Přihlaš se</a></p>

            <form method="POST" action="">
                <label for="email">Email
                    <input type="email" name="email" id="email" placeholder="name@example.com"
                        value="<?php if(isset($email)){ echo htmlspecialchars($email); } ?>" required>
                </label><br>

                <label for="username">Uživatelské jméno
                    <input type="text" name="username" id="username" placeholder="Uživatelské jméno"
                        value="<?php if(isset($uzivatel)){ echo htmlspecialchars($uzivatel); } ?>" required>
                </label><br>

                <label for="password">Heslo
                    <input type="password" name="password" id="password" placeholder="Min. 8 znaků" required>
                </label><br>

                <label for="password2">Heslo znovu
                    <input type="password" name="password2" id="password2" placeholder="Zopakuj heslo" required>
                </label><br>

                <input type="submit" name="registrace" value="Zaregistrovat se" class="btn-submit">
            </form>

            <a href="index.php" class="link-back">Zpět na hlavní stránku</a>
        </div>

    </div>

    <footer class="footer">
        <span>2026 Slovníček</span>
        <span>Made by Soukup Jakub</span>
    </footer>

</div>
</body>
</html>