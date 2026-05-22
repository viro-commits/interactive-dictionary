<?php
session_start();
require 'dbconnect.php';

// SPOJENÍ S MYMEMORY API
function translate($text, $jazyky = "cs|en"){

    // KROK 1: STAŽENÍ DAT Z API
    $url = "https://api.mymemory.translated.net/get?q=" . urlencode($text) . "&langpair=" . $jazyky;
    $response = @file_get_contents($url);
    $data = json_decode($response, true);

    // POKUD API NIC NEVRÁTÍ, VRÁTÍ SE PRÁZDNÉ POLE
    if(!isset($data["matches"]) or !is_array($data["matches"])){
        return [];
    }

    $matches = $data["matches"];

    // KROK 2: ODFILTROVÁNÍ ŠPATNÝCH PŘEKLADŮ (prázdné nebo nízká kvalita)
    $dobre = [];
    foreach($matches as $m){
        $kvalita = 0;
        if(isset($m["quality"])){
            $kvalita = (int)$m["quality"];
        }

        $preklad = "";
        if(isset($m["translation"])){
            $preklad = trim($m["translation"]);
        }

        //  PŘIDÁ SE JEN POKUD JE PŘEKLAD NEPRÁZDNÝ, MÁ DOSTATEČNOU KVALITU A NENÍ PŘÍLIŠ DLOUHÝ
        $pocet_slov = count(explode(" ", $preklad));
        if($preklad != "" && $kvalita >= 60 && $pocet_slov <= 2){
            $dobre[] = $m;
        }
    }

    // POKUD PO FILTROVÁNÍ NIC NEZBYLO, VRÁTÍ ASPOŇ PRVNÍ VÝSLEDEK
    if(count($dobre) == 0 && count($matches) > 0){
        foreach($matches as $m){
            if(isset($m["translation"])){
                $preklad = trim($m["translation"]);
            }else{
                $preklad = "";
            }
            if(count(explode(" ", $preklad)) <= 2){
                $dobre[] = $m;
                break;
            }
    }
        // POKUD ANI TO NIC NENAJDE, VEZME SE ASPOŇ PRVNÍ VÝSLEDEK
        if(count($dobre) == 0){
            $dobre[] = $matches[0];
        }
    }

    // KROK 3: SEŘAZENÍ OD NEJLEPŠÍ KVALITY PO NEJHORŠÍ (BUBBLESORT)
    $pocet = count($dobre);
    for($i = 0; $i < $pocet - 1; $i++){
        for($j = 0; $j < $pocet - $i - 1; $j++){
            $kvalita_aktualni = 0;
            if(isset($dobre[$j]["quality"])){
                $kvalita_aktualni = (int)$dobre[$j]["quality"];
            }

            $kvalita_nasledujici = 0;
            if(isset($dobre[$j + 1]["quality"])){
                $kvalita_nasledujici = (int)$dobre[$j + 1]["quality"];
            }

            if($kvalita_aktualni < $kvalita_nasledujici){
                $pom = $dobre[$j];
                $dobre[$j] = $dobre[$j + 1];
                $dobre[$j + 1] = $pom;
            }
        }
    }

    // KROK 4: VRÁTÍ MAXIMÁLNĚ 5 NEJLEPŠÍCH VÝSLEDKŮ
    $vysledek = [];
    $max_pocet = 5;
    $i = 0;
    foreach($dobre as $m){
        if($i >= $max_pocet){
            break;
        }
        $vysledek[] = $m;
        $i++;
    }

    return $vysledek;
}

// SPOJENÍ S DICTIONARY API PRO DEFINICE V ANGLIČTINĚ
function definiceslova($slovo){
    $url = "https://api.dictionaryapi.dev/api/v2/entries/en/" . urlencode($slovo);
    $response = @file_get_contents($url);
    $data = json_decode($response, true);
    $vysledky = [];

    if(isset($data[0]["meanings"])){
        foreach($data[0]["meanings"] as $meaning){
            if(isset($meaning["definitions"])){
                foreach($meaning["definitions"] as $def){
                    if(isset($def["definition"])){
                        $vysledky[] = $def["definition"];
                    }
                }
            }
        }
    }
    return $vysledky;
}

// OČISTĚNÍ OD BOS (Beginning of Sentence) ZNAČEK
function cistit_preklad($text){
    $text = str_replace(["BOS:", "BOS", "bos:"], "", $text);
    return trim($text, ".,!?;: ");
}

// ODSTRANĚNÍ ČLENŮ
function odstrancleny($slovo){
    $cleny = ["der", "die", "das", "ein", "eine", "einen", "the", "a", "an", "le", "la", "les", "un", "une", "des", "du", "de la", "un", "unos", "una", "unas", "el", "los", "las"];

    foreach($cleny as $clen){
        // ODSTRANÍ SE POUZE POKUD JE ČLEN NA ZAČÁTKU A ZA NÍM NÁSLEDUJE DALŠÍ MEZERA + DALŠÍ SLOVO
        $slovo = preg_replace('/^' . preg_quote($clen, '/') . '\s+/i', '', $slovo);
    }

    return trim($slovo);
}

// INICIALIZACE PROMĚNNÝCH 
$prihlaseny = isset($_SESSION["user_id"]);
$puvodnitext = "";
$vysledek = "";
$vybrane = "";
$alternativy = [];
$definice_pole = [];
$smer = "cs|en";
$zprava = "";

if(isset($_POST['smer'])){
    $smer = $_POST['smer'];
}

// ZPRACOVÁNÍ PŘÍCHOZÍHO TEXTU 
if($_POST && isset($_POST['text']) && $_POST['text'] != ""){
    $puvodnitext = mb_strtolower(trim($_POST['text']));

    if(isset($_POST["smer"])){
    $smer = $_POST["smer"];
    }else{
        $smer = "";
    }

    $vsechny_shody = translate($puvodnitext, $smer);
    $vysledek = "";
    $alternativy = [];

    if(!empty($vsechny_shody)){
        $vysledek = cistit_preklad($vsechny_shody[0]['translation']);
        $max_alt = 3;
        $i = 1;

        foreach($vsechny_shody as $shoda){
            if($i > $max_alt){
                break;
            }

            $alt = cistit_preklad($shoda['translation']);

            if(strtolower($alt) != strtolower($vysledek) && !in_array($alt,$alternativy) && $alt != ""){
                $alternativy[] = $alt;
                $i++;
            }

        }
    }

    // ZÍSKÁNÍ DEFINIC
    if(strpos($smer, "en") !== false){
        if(substr($smer, 0, 2) == "en"){
            // SMĚR: EN -> CS  (vstup je anglicky)
            $hledane_slovo = $puvodnitext;
        }else{
            // SMĚR: CS -> EN  (výstup je anglicky)
            if(isset($_POST['vybrane_slovo']) && $_POST['vybrane_slovo'] != ""){
                $hledane_slovo = $_POST['vybrane_slovo'];
            }else{
                $hledane_slovo = $vysledek;
            }
        }

        $cisteslovo = str_ireplace(['the ', 'a ', 'an '], "", $hledane_slovo);
        $definice_pole = definiceslova(trim($cisteslovo));
    }
}

// UKLÁDÁNÍ DO SLOVNÍČKU
if(isset($_POST["ulozit_slovicko"]) && $prihlaseny){
    $cz = trim($_POST["slovo_cz"]);
    $translation = trim($_POST["slovo_preklad"]);

    $cz_pro_regex = odstrancleny($cz);
    $translation_pro_regex = odstrancleny($translation);

    // VALIDACE 1: NEPOVOLENÉ ZNAKY
    if(!preg_match('/^\p{L}+(\s\p{L}+)*(-\p{L}+)*$/u', $cz_pro_regex)
    or
    !preg_match('/^\p{L}+(\s\p{L}+)*(-\p{L}+)*$/u', $translation_pro_regex)){
        $zprava = "Slovíčko obsahuje nepovolené znaky!";

    }else{
        if(isset($_POST["definice"])){
            $vyznam = trim($_POST["definice"]);
        }else{
            $vyznam = "";
        }

    // VALIDACE 3: PRÁZDNÉ SLOVÍČKO
    if($cz == "" or $translation == ""){
        $zprava = "Nelze uložit prázdné slovíčko!";
    }else{
        $user_id = $_SESSION["user_id"];
        $cz = ucfirst(strtolower($cz_pro_regex));
        $cz = mysqli_real_escape_string($conn, $cz);
        $translation = ucfirst(strtolower($translation_pro_regex));
        $translation = mysqli_real_escape_string($conn, $translation);
        $lang_code = mysqli_real_escape_string($conn, $_POST["target_lang"]);

        $sql = "SELECT id FROM languages WHERE lang_code = '$lang_code'";
        $result = mysqli_query($conn, $sql);
        $lang_data = mysqli_fetch_assoc($result);
        $lang_id = $lang_data["id"];

        // VALIDACE 4: DUPLICITA
        $sql = "SELECT id FROM words 
                WHERE cz = '$cz' 
                AND translation = '$translation' 
                AND user_id = '$user_id' 
                AND lang_id = '$lang_id'";
        $kontrola = mysqli_query($conn, $sql);

        if(mysqli_num_rows($kontrola) > 0){
            $zprava = "Toto slovíčko v tomto jazyce ve slovníku již máš!";
        }else{
            $finalnivyznam = mysqli_real_escape_string($conn, $vyznam);
            $sql = "INSERT INTO words (user_id, cz, translation, lang_id, vyznam) 
                    VALUES ('$user_id', '$cz', '$translation', '$lang_id', '$finalnivyznam')";
            mysqli_query($conn, $sql);
            $zprava = "Slovíčko bylo úspěšně uloženo!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Překladač</title>

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
            justify-content: flex-start;
            padding: 24px 16px;
        }

        .wrapper{
            width: 100%;
            max-width: 780px;
        }

        /* HEADER */
        .site-header{
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #d0cdc7;
        }

        .header-left h1{
            font-size: 2rem;
            font-weight: 800;
            color: #1c1c1c;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .header-left h1 span{
            color: #cc1100;
        }

        .header-left .tagline{
            margin-top: 5px;
            font-size: 0.83rem;
            color: #aaa;
        }

        .header-right{
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .header-right a{
            font-size: 0.85rem;
            font-weight: 500;
            color: #888;
            text-decoration: none;
            transition: color 0.2s;
        }

        .header-right a:hover{
            color: #cc1100;
        }

        /* HLAVNÍ GRID */
        .page-grid{
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 16px;
            align-items: start;
        }

        /* PŘEKLADAČ (VLEVO) */
        .prekladac{
            background: #eeece8;
            border-radius: 12px;
            padding: 28px 26px;
            border: 1px solid #d0cdc7;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .prekladac form {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* SELECT JAZYKA */
        .lang-select{
            background: #e2e0db;
            color: #1c1c1c;
            border: 1px solid #d0cdc7;
            border-radius: 8px;
            padding: 10px 36px 10px 14px;
            font-size: 0.9rem;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='none' stroke='%23888' stroke-width='1.5' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            transition: border-color 0.2s;
            width: 100%;
        }

        .lang-select:focus{
            outline: none;
            border-color: #cc1100;
        }

        /* TEXT AREA */
        .input-text{
            width: 100%;
            min-height: 100px;
            background: #e2e0db;
            color: #1c1c1c;
            border: 1px solid #d0cdc7;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 0.9rem;
            font-family: 'Roboto', sans-serif;
            resize: vertical;
            transition: border-color 0.2s;
        }

        .input-text:focus{
            outline: none;
            border-color: #cc1100;
        }

        .input-text::placeholder{
            color: #bbb;
        }

        /* TLAČÍTKA */
        .btn{
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 22px;
            background-color: #cc1100;
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover{
            background-color: #aa0e00;
        }

        .btn-wide{
            width: 100%;
        }

        .btn-ulozit{
            background-color: #eeece8;
            color: #1c1c1c;
            border: 1px solid #d0cdc7;
        }

        .btn-ulozit:hover{
            border-color: #cc1100;
            background-color: #f5ede9;
            color: #cc1100;
        }

        /* VÝSLEDKY (VPRAVO) */
        .vysledky-panel{
            background: #eeece8;
            border-radius: 12px;
            padding: 28px 26px;
            border: 1px solid #d0cdc7;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .vysledky-panel .panel-label{
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #aaa;
            margin-bottom: 2px;
        }

        /* RADIO KARTY */
        .radio-group{
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .radio-group label{
            display: flex;
            align-items: center;
            gap: 10px;
            background: #e2e0db;
            border: 1px solid #d0cdc7;
            border-radius: 8px;
            padding: 10px 14px;
            color: #1c1c1c;
            cursor: pointer;
            font-size: 0.9rem;
            transition: border-color 0.2s, background 0.2s;
        }

        .radio-group label:has(input:checked),
        .radio-group label:hover{
            border-color: #cc1100;
            background: #f5ede9;
        }

        .radio-group input[type="radio"]{
            accent-color: #cc1100;
        }

        .radio-group input[type="radio"]:disabled{
            cursor: not-allowed;
            opacity: 0.8;
        }

        .radio-group label:has(input:disabled){
            cursor: not-allowed;
            opacity: 0.7;
        }

        .radio-group label:has(input:disabled):hover{
            background: #e2e0db;
            border-color: #d0cdc7;
        }

        /* DEFINICE */
        .definice-sekce{
            margin-bottom: 8px
        }


        .definice-sekce ul{
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .definice-sekce ul::-webkit-scrollbar{
            width: 4px;
        }

        .definice-sekce ul::-webkit-scrollbar-track{
            background: transparent;
        }

        .definice-sekce ul::-webkit-scrollbar-thumb{
            background: #d0cdc7;
            border-radius: 4px;
        }

        .definice-sekce li label{
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #e2e0db;
            border: 1px solid #d0cdc7;
            border-radius: 8px;
            padding: 10px 14px;
            color: #444;
            cursor: pointer;
            font-size: 0.85rem;
            line-height: 1.5;
            transition: border-color 0.2s, background 0.2s;
        }

        .definice-sekce li label:has(input:checked),
        .definice-sekce li label:hover{
            border-color: #cc1100;
            background: #f5ede9;
        }

        .definice-sekce input[type="radio"]{
            accent-color: #cc1100;
            margin-top: 3px;
            flex-shrink: 0;
        }

        /* ZPRÁVA */
        .zprava{
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            border: 1px solid #d0cdc7;
            color: #444;
            background: #e2e0db;
        }

        /* UPOZORNĚNÍ + HINT */
        .upozorneni{
            font-size: 0.8rem;
            color: #888;
            line-height: 1.5;
            padding: 8px 12px;
            border: 1px solid #d0cdc7;
            border-radius: 8px;
            background: #e2e0db;
        }

        .hint{
            color: #aaa;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .hint a, .api-link{
            color: #cc1100;
            text-decoration: none;
        }

        .hint a:hover, .api-link:hover{
            text-decoration: underline;
        }

        /* PATIČKA */
        .footer{
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #999;
        }

        /* RESPONSIVE */
        @media (max-width: 620px){
            .page-grid{
                grid-template-columns: 1fr;
            }

            .site-header{
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">

    <header class="site-header">
        <div class="header-left">
            <h1>Slovníček<span>.</span></h1>
            <p class="tagline">Překladač</p>
        </div>
        <nav class="header-right">
            <a href="index.php">Hlavní stránka</a>
            <?php if($prihlaseny){ ?>
                <a href="slovicka.php">Slovník</a>
                <a href="zkouseni.php">Zkoušení</a>
                <a href="statistiky.php">Statistiky</a>
                <a href="balicek.php">Balíčky slov</a>
                <a href="profil.php">Profil</a>
            <?php } ?>
        </nav>
    </header>

    <div class="page-grid">

        <!-- PŘEKLADAČ (VLEVO) -->
        <div class="prekladac">
            <form method="post">
                    <select class="lang-select" name="smer">
                        <optgroup label="Z češtiny">
                            <option value="cs|en" <?php if($smer == "cs|en") { echo "selected"; } ?>>CS -> EN</option>
                            <option value="cs|de" <?php if($smer == "cs|de") { echo "selected"; } ?>>CS -> DE</option>
                            <option value="cs|fr" <?php if($smer == "cs|fr") { echo "selected"; } ?>>CS -> FR</option>
                            <option value="cs|es" <?php if($smer == "cs|es") { echo "selected"; } ?>>CS -> ES</option>
                        </optgroup>
                        <optgroup label="Z cizích jazyků">
                            <option value="en|cs" <?php if($smer == "en|cs") { echo "selected"; } ?>>EN -> CS</option>
                            <option value="de|cs" <?php if($smer == "de|cs") { echo "selected"; } ?>>DE -> CS</option>
                            <option value="fr|cs" <?php if($smer == "fr|cs") { echo "selected"; } ?>>FR -> CS</option>
                            <option value="es|cs" <?php if($smer == "es|cs") { echo "selected"; } ?>>ES -> CS</option>
                        </optgroup>
                    </select>

                    <textarea class="input-text" name="text" placeholder="Zadejte slovo..."><?php echo htmlspecialchars($puvodnitext); ?></textarea>
                    <button type="submit" class="btn btn-wide">Přeložit</button>
            </form>

            <p class="upozorneni">Překlady někdy nemusí být přesné. Výsledky vždy ověř.</p>  

            <p class="hint">
                Využívá <a href="https://mymemory.translated.net" target="_blank" class="api-link">MyMemory API</a>
                a <a href="https://dictionaryapi.dev" target="_blank" class="api-link">Dictionary API</a>.
            </p>
        </div>

        <!-- PANEL S VÝSLEDKY (VPRAVO) -->
        <div class="vysledky-panel">
            <?php if($vysledek != ""){ ?>

                <form method="post" id="formular_alternativa">
                    <input type="hidden" name="smer" value="<?php echo htmlspecialchars($smer); ?>">
                    <input type="hidden" name="text" value="<?php echo htmlspecialchars($puvodnitext); ?>">

                    <p class="panel-label">Překlad</p>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="vybrane_slovo" value="<?php echo htmlspecialchars($vysledek); ?>"
                                <?php if(!isset($_POST["vybrane_slovo"]) or $_POST["vybrane_slovo"] == $vysledek){
                                    echo 'checked';
                                    } ?>
                                <?php if(!$prihlaseny){
                                    echo 'disabled';
                                    } ?>
                                onchange="document.getElementById('formular_alternativa').submit()">
                            <?php echo htmlspecialchars($vysledek); ?>
                        </label>

                        <?php foreach($alternativy as $alt){ ?>
                            <label>
                                <input type="radio" name="vybrane_slovo"
                                    value="<?php echo htmlspecialchars($alt); ?>"
                                    <?php if(isset($_POST["vybrane_slovo"]) && $_POST["vybrane_slovo"] == $alt){
                                        echo 'checked';
                                        } ?>
                                    <?php if(!$prihlaseny){
                                        echo 'disabled';
                                        } ?>
                                    onchange="document.getElementById('formular_alternativa').submit()">
                                <?php echo htmlspecialchars($alt); ?>
                            </label>
                        <?php } ?>
                    </div>
                </form>

                <?php if(!$prihlaseny){ ?>
                    <p class="hint">Pro výběr alternativ a definice se <a href="prihlaseni.php">přihlaš</a>.</p>
                <?php } ?>

            <?php }else{ ?>
                <p class="hint">Výsledek překladu se zobrazí zde.</p>
            <?php } ?>

            <?php
            if($prihlaseny){
                $slovo_cz = "";
                $slovo_preklad = "";
                $target_lang = "";

                if($vysledek != ""){
                    $smer_prekladu = explode('|', $smer);
                    $z_cestiny = ($smer_prekladu[0] == 'cs');
                    if(isset($_POST['vybrane_slovo']) && $_POST["vybrane_slovo"] != ""){
                        $vybrane = $_POST['vybrane_slovo'];
                    }else{
                        $vybrane = $vysledek;
                    }

                    if($z_cestiny == true){
                        $slovo_cz = $puvodnitext;
                        $slovo_preklad = $vybrane;
                        $target_lang = $smer_prekladu[1];
                    }else{
                        $slovo_cz = $vybrane;
                        $slovo_preklad = $puvodnitext;
                        $target_lang = $smer_prekladu[0];
                    }
                }
            ?>
                <!-- FORMULÁŘ PRO ULOŽENÍ -->
                <?php if($vysledek != ""){ ?>
                <form method="post">
                    <input type="hidden" name="slovo_cz" value="<?php echo htmlspecialchars($slovo_cz); ?>">
                    <input type="hidden" name="slovo_preklad" value="<?php echo htmlspecialchars($slovo_preklad); ?>">
                    <input type="hidden" name="target_lang" value="<?php echo htmlspecialchars($target_lang); ?>">
                    <input type="hidden" name="text" value="<?php echo htmlspecialchars($puvodnitext); ?>">
                    <input type="hidden" name="smer" value="<?php echo htmlspecialchars($smer); ?>">
                    <input type="hidden" name="vybrane_slovo" value="<?php echo htmlspecialchars($vybrane); ?>">

                    <!-- VYPSÁNÍ DEFINIC -->
                    <?php if(($smer == "cs|en" or $smer == "en|cs") && $vysledek != ""){ ?>
                        <div class="definice-sekce">
                            <p class="panel-label">Významy v angličtině</p>
                            <ul>
                                <?php
                                $limit = 0;
                                foreach($definice_pole as $def){
                                    if($limit < 5){ ?>
                                        <li>
                                            <label>
                                                <input type="radio" name="definice" value="<?php echo htmlspecialchars($def); ?>"
                                                    <?php if ($limit == 0) { echo "checked"; } ?>>
                                                <?php echo htmlspecialchars($def); ?>
                                            </label>
                                        </li>
                                    <?php $limit++;
                                    }
                                } ?>
                            </ul>
                        </div>
                    <?php } ?>

                    <?php if($slovo_preklad != ""){ ?>
                        <button type="submit" name="ulozit_slovicko" class="btn btn-ulozit btn-wide">Uložit do slovníčku</button>
                        <p class="hint">Ukládej pouze slovíčka, která dávají smysl. Nesmysly lze mazat ve <a href="slovicka.php">Slovníčku</a>.</p>
                    <?php } ?>
                </form>

                <?php if($zprava != "" && isset($_POST["ulozit_slovicko"])){ ?>
                    <p class="zprava"><?php echo htmlspecialchars($zprava); ?></p>
                <?php } ?>
                <?php } ?>

            <?php } ?>
        </div>

    </div>

    <footer class="footer">
        <span>2026 Slovníček</span>
        <span>Made by Soukup Jakub</span>
    </footer>

</div>
</body>
</html>