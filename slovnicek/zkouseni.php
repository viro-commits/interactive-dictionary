<?php
session_start();
require 'dbconnect.php';
require 'starterpack.php';

// VYKRESLÍ FORMULÁŘ PRO ULOŽENÍ SLOVÍČKA Z BALÍČKU DO UŽIVATELOVA SLOVNÍKU
function ulozit_formular($slovo, $vybrany_jazyk){
    echo "<div class=\"ulozit-karta\">";
    echo "<p>Líbí se ti toto slovíčko? Přidej si ho do slovníku.</p>";
    echo "<form method=\"post\">";
    echo "<input type=\"hidden\" name=\"action\" value=\"ulozit_do_db\">";
    echo "<input type=\"hidden\" name=\"cz\" value=\"" . htmlspecialchars($slovo["cz"]) . "\">";
    echo "<input type=\"hidden\" name=\"translation\" value=\"" . htmlspecialchars($slovo["translation"]) . "\">";
    if($vybrany_jazyk == "en"){
        echo "<input type=\"hidden\" name=\"vyznam\" value=\"" . htmlspecialchars($slovo["vyznam"]) . "\">";
    }
    echo "<input type=\"hidden\" name=\"lang_code\" value=\"" . htmlspecialchars($vybrany_jazyk) . "\">";
    echo "<button type=\"submit\" class=\"btn-ulozit\">Uložit do slovníku</button>";
    echo "</form></div>";
}

// ULOŽÍ HODNOCENÍ DO HISTORIE A AKTUALIZUJE LEVEL + NEXT_REVIEW SLOVÍČKA (SPACED REPETITION)
// red -> level 1, za 1 min 
// yellow -> level stejný, za 30 min
// green -> level +1, za N dní
function ulozit_hodnoceni($conn, $user_id, $word_id, $rating){
    if($word_id <= 0){
        return; // SLOVÍČKO Z BALÍČKU (NENÍ V DB), NEUKLÁDÁ SE
    }

    $sql = "INSERT INTO history (user_id, word_id, rating) 
            VALUES ('$user_id', '$word_id', '$rating')";
    mysqli_query($conn, $sql);

    $sql = "SELECT level FROM words WHERE id = '$word_id'";
    $response = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($response);
    $ted_level = $row["level"];

    if($rating == "red"){
        $novy_level = 1;
        $interval = "1 MINUTE";
    }elseif ($rating == "yellow"){
        $novy_level = $ted_level;
        $interval = "30 MINUTE";
    }else{
        $novy_level = $ted_level + 1;
        $interval = "$novy_level DAY";
    }

    $sql = "UPDATE words 
            SET level = '$novy_level', 
            next_review = DATE_ADD(now(), 
            INTERVAL $interval)
            WHERE id = '$word_id' AND user_id = '$user_id'";
    mysqli_query($conn, $sql);
}

// KONTROLA, ZDA JE UŽIVATEL PŘIHLÁŠENÝ, POKUD NE, TAK HO PŘESMĚRUJEME NA prihlaseni.php
if(!isset($_SESSION["user_id"])){
    header("Location: prihlaseni.php");
    exit();
}

// ZÍSKÁNÍ ID UŽIVATELE
$user_id = $_SESSION["user_id"];

// VÝBĚR JAZYKA, "en" JE DEFAULT
$vybrany_jazyk = "en";
if (isset($_GET["lang"]) && isset($starter_pack[$_GET["lang"]])) {
    $vybrany_jazyk = $_GET["lang"];
}

// NAČTENÍ BALÍČKU (JAKÝ JAZYK) A PŘEKLAD LANG_CODE NA LANG_ID Z DATABÁZE
$balicek = $starter_pack[$vybrany_jazyk];
$sql = "SELECT id FROM languages WHERE lang_code = '$vybrany_jazyk'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$lang_id = $row["id"];

// MÓDY, "flashcard" JE DEFAULT
$povolene_mody = ["flashcard", "multiplechoice", "doplnit", "mujslovnik", "mujslovnik_doplnit"];
$mode = "flashcard";
if(isset($_GET["mode"]) && in_array($_GET["mode"], $povolene_mody)){
    $mode = $_GET["mode"];
}

// HODNOCENÍ
if(isset($_POST["rating"])){
    unset($_SESSION["save_msg"]);
    $word_id = intval($_POST["word_id"]);
    $rating = $_POST["rating"];

    if ($rating == "green"){
        $_SESSION["last_result"] = "spravne";
    }elseif($rating == "yellow"){
        $_SESSION["last_result"] = "skoro";
    }else{
        $_SESSION["last_result"] = "spatne";
    }

    ulozit_hodnoceni($conn, $user_id, $word_id, $rating);

    header("Location: zkouseni.php?lang=$vybrany_jazyk&mode=$mode");
    exit();
}

// ULOŽENÍ ZE STARTERPACKU
if(isset($_POST["action"]) && $_POST["action"] == "ulozit_do_db"){
    $cz = mysqli_real_escape_string($conn, $_POST["cz"]);
    $translation = mysqli_real_escape_string($conn, $_POST["translation"]);
    $lang_code = $_POST["lang_code"];
    $vyznam = "";

    if($lang_code == 'en' && isset($_POST["vyznam"])){
        $vyznam = mysqli_real_escape_string($conn, $_POST["vyznam"]);
    }

    $sql = "SELECT id FROM words 
            WHERE user_id = '$user_id' AND cz = '$cz' AND lang_id = '$lang_id'";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) > 0){
        $_SESSION["save_msg"] = "exists"; // SLOVÍČKO EXISTUJE
    }else{
        $sql = "INSERT INTO words (user_id, cz, translation, vyznam, lang_id, level, next_review) 
                VALUES ('$user_id', '$cz', '$translation', '$vyznam', '$lang_id', 1, NOW())";
        mysqli_query($conn, $sql);
        $_SESSION["save_msg"] = "saved"; // SLOVÍČKO ULOŽENO
    }

    header("Location: zkouseni.php?lang=$vybrany_jazyk&mode=$mode");
    exit();
}

// ZOBRAZENÍ SLOVÍČEK
$je_to_z_databaze = false;
$slovo = null;

// UŽIVATEL SI MŮŽE VYBRAT, ZDA SE CHCE ZKOUŠET JENOM ZE SVÉHO SLOVNÍKU NEBO ZE SVÉHO SLOVNÍKU + "EXTRA" BALÍČKU
if($mode == "mujslovnik" or $mode == "mujslovnik_doplnit"){
    $sql = "SELECT id, cz, translation, vyznam, level, lang_id 
            FROM words
            WHERE user_id = '$user_id'
            AND lang_id = '$lang_id'
            AND next_review <= NOW()
            ORDER BY RAND()
            LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $slovo = mysqli_fetch_assoc($result);
        $je_to_z_databaze = true;
    }
}else{
    $sql = "SELECT id, cz, translation, vyznam, level, lang_id 
            FROM words
            WHERE user_id = '$user_id'
            AND lang_id = '$lang_id'
            AND next_review <= NOW()
            ORDER BY next_review ASC
            LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $slovo = mysqli_fetch_assoc($result);
        $je_to_z_databaze = true;
    }else{
        $slovo = $balicek[array_rand($balicek)];
        $slovo["id"] = 0;
        $slovo["lang_code"] = $vybrany_jazyk;
    }
}

// MÓD MULTIPLE CHOICE
$moznosti = [];
if($mode == "multiplechoice" && $slovo != null){
    $moznosti[] = $slovo["cz"];

    // VÝBĚR DISTRACTORŮ (FALEŠNÉ MOŽNOSTI)
    $sql = "SELECT cz 
            FROM words 
            WHERE lang_id = '$lang_id' 
            AND id != '" . $slovo['id'] . "' 
            ORDER BY RAND() 
            LIMIT 10";
    $response = mysqli_query($conn, $sql);

    $pom_pole = [];
    while($row = mysqli_fetch_assoc($response)){
        $pom_pole[] = $row["cz"];
    }

    foreach($balicek as $b_slovo){
        if($b_slovo["cz"] != $slovo["cz"]){
            $pom_pole[] = $b_slovo["cz"];
        }
    }

    $pom_pole = array_unique($pom_pole); // array_unique SE ZBAVÍ SLOV, KTERÉ SE ZDE VYSKYTUJÍ VÍCKRÁT
    shuffle($pom_pole);

    // LIMIT NA 3 FALEŠNÉ MOŽNOSTI (4 SLOVA, 1 SPRÁVNĚ - 3 ŠPATNĚ)
    $counter = 0;
    foreach($pom_pole as $falesne_slovo){
        if($counter < 3 && !in_array($falesne_slovo, $moznosti)){
            $moznosti[] = $falesne_slovo;
            $counter++;
        }
    }

    shuffle($moznosti); // ZAMÍCHAJÍ SE VŠECHNY MOŽNOSTI
}

// MÓD DOPLŇOVÁNÍ
if(isset($_POST["doplnit_odpoved"])){
    unset($_SESSION["save_msg"]);
    $word_id = intval($_POST["word_id"]);
    $zadana = trim(mb_strtolower($_POST["doplnit_odpoved"]));
    $spravna = trim(mb_strtolower($_POST["spravna_odpoved"]));

    if($zadana == $spravna){
        $rating = "green";
        $_SESSION["last_result"] = "spravne";
        unset($_SESSION["last_result_doplnit"]);
    }else{
        $rating = "red";
        $_SESSION["last_result_doplnit"] = [
            "zadana"  => $_POST["doplnit_odpoved"],
            "spravna" => $_POST["spravna_odpoved"]
        ];
        $_SESSION["last_result"] = "spatne";
    }

    ulozit_hodnoceni($conn, $user_id, $word_id, $rating);

    header("Location: zkouseni.php?lang=$vybrany_jazyk&mode=$mode");
    exit();
}

// KONTROLA, ZDA MÁ UŽIVATEL VŮBEC NĚJAKÁ SLOVÍČKA V DANÉM JAZYCE
$sql = "SELECT COUNT(*) as pocet FROM words
        WHERE user_id = '$user_id' AND lang_id = '$lang_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
if($row["pocet"] > 0){
    $ma_slovicka = true;
}else{
    $ma_slovicka = false;
}


// RESETOVÁNÍ NEXT_REVIEW - UŽIVATEL CHCE ZKOUŠET ZNOVU
if(isset($_POST["action"]) && $_POST["action"] == "reset_review"){
    $sql = "UPDATE words
            SET next_review = NOW()
            WHERE user_id = '$user_id' AND lang_id = '$lang_id'";
    mysqli_query($conn, $sql);

    header("Location: zkouseni.php?lang=$vybrany_jazyk&mode=$mode");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zkoušení</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body{
            background-color: #e2e0db;
            color: #1c1c1c;
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            padding: 24px 16px;
        }

        .wrapper{
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* HEADER */
        .site-header{
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
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
            grid-template-columns: 260px 1fr;
            gap: 16px;
            align-items: stretch;
        }

        /* LEVÝ PANEL */
        .panel{
            background-color: #eeece8;
            border: 1px solid #d0cdc7;
            border-radius: 16px;
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .panel h1{
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.4px;
            text-transform: uppercase;
            color: #1c1c1c;
        }

        .panel-sekce{
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .panel-sekce h4{
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #aaa;
            margin-bottom: 2px;
        }

        .panel-sekce .btn-group{
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* FILTR TLAČÍTKA */
        .btn-filter{
            display: inline-block;
            padding: 8px 16px;
            background-color: #e2e0db;
            color: #888;
            border: 1px solid #d0cdc7;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.82rem;
            text-decoration: none;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
        }

        .btn-filter:hover{
            background-color: #f5ede9;
            color: #cc1100;
            border-color: #cc1100;
        }

        .btn-filter.aktivni{
            background-color: #f5ede9;
            color: #cc1100;
            border-color: #cc1100;
        }

        /* PRAVÁ KARTA */
        .zkouseni-karta{
            background-color: #eeece8;
            border: 1px solid #d0cdc7;
            border-radius: 16px;
            padding: 36px 32px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            min-height: 360px;
        }

        .zdroj-info{
            color: #aaa;
            font-size: 0.82rem;
            font-style: italic;
            font-weight: 300;
        }

        /* FEEDBACK */
        .feedback-banner{
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
        }

        .feedback-banner.spravne{
            background-color: #edf7f0;
            border: 1px solid #a8d5b5;
            color: #2a7a47;
        }

        .feedback-banner.skoro{
            background-color: #fdf8e8;
            border: 1px solid #d4c46a;
            color: #7a6a1a;
        }

        .feedback-banner.spatne{
            background-color: #fdf0ee;
            border: 1px solid #e8b8b3;
            color: #aa2a1a;
        }

        /* ZPRÁVA O ULOŽENÍ */
        .save-msg{
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .save-msg.saved{
            background-color: #edf7f0;
            border: 1px solid #a8d5b5;
            color: #2a7a47;
        }

        .save-msg.exists{
            background-color: #fdf8e8;
            border: 1px solid #d4c46a;
            color: #7a6a1a;
        }

        /* HLAVNÍ SLOVO */
        .slovo-hlavni{
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #1c1c1c;
            line-height: 1.2;
        }

        /* MULTIPLE CHOICE */
        .choices{
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .choices form{
            display: block;
        }

        .btn-choice{
            width: 100%;
            padding: 16px 20px;
            background-color: #e2e0db;
            color: #1c1c1c;
            border: 1px solid #d0cdc7;
            border-radius: 12px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: left;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.15s, border-color 0.2s;
        }

        .btn-choice:hover{
            background-color: #f5ede9;
            border-color: #cc1100;
            transform: translateY(-2px);
            color: #cc1100;
        }

        /* FLASHCARD */
        details{
            background-color: #e2e0db;
            border: 1px solid #d0cdc7;
            border-radius: 12px;
            overflow: hidden;
        }

        details summary{
            padding: 16px 20px;
            font-weight: 600;
            font-size: 0.95rem;
            color: #444;
            cursor: pointer;
            list-style: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }

        details summary::-webkit-details-marker{
            display: none;
        }
    
        details summary:hover{
            background-color: #d8d5d0;
        }

        .details-obsah{
            padding: 20px;
            border-top: 1px solid #d0cdc7;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .details-obsah h2{
            font-size: 1.6rem;
            font-weight: 700;
            color: #1c1c1c;
        }

        .details-obsah .vyznam{
            color: #888;
            font-size: 0.9rem;
            font-style: italic;
            font-weight: 300;
        }

        /* DOPLŇOVÁNÍ */
        .doplnit-form{
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .doplnit-form input[type="text"]{
            padding: 14px 18px;
            background-color: #e2e0db;
            border: 1px solid #d0cdc7;
            border-radius: 12px;
            color: #1c1c1c;
            font-family: 'Roboto', sans-serif;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
            width: 100%;
        }

        .doplnit-form input[type="text"]:focus{
            border-color: #cc1100;
        }

        .btn-odeslat{
            padding: 14px 28px;
            background-color: #1c1c1c;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.15s;
            align-self: flex-start;
        }

        .btn-odeslat:hover{
            background-color: #cc1100;
        }

        .doplnit-spravna{
            font-size: 0.9rem;
            color: #888;
            padding: 10px 14px;
            background-color: #e2e0db;
            border-radius: 8px;
        }

        .doplnit-spravna strong{
            color: #2a7a47;
        }

        /* HODNOTÍCÍ TLAČÍTKA */
        .rating-form{
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-rating{
            flex: 1;
            min-width: 100px;
            padding: 12px 16px;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            border: 1px solid;
            transition: transform 0.15s, opacity 0.2s;
        }

        .btn-rating:hover{
            opacity: 0.85;
        }

        .btn-rating.red{
            background-color: #fdf0ee;
            color: #aa2a1a;
            border-color: #e8b8b3;
        }

        .btn-rating.yellow{
            background-color: #fdf8e8;
            color: #7a6a1a;
            border-color: #d4c46a;
        
        }
        .btn-rating.green{
            background-color: #edf7f0;
            color: #2a7a47;
            border-color: #a8d5b5;
        }

        /* ULOŽIT DO SLOVNÍKU */
        .ulozit-karta{
            background-color: #e2e0db;
            border: 1px dashed #d0cdc7;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .ulozit-karta p{
            color: #888;
            font-size: 0.88rem;
            font-weight: 300;
        }

        .btn-ulozit{
            padding: 10px 22px;
            background-color: #eeece8;
            color: #1c1c1c;
            border: 1px solid #d0cdc7;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color 0.2s, border-color 0.2s, color 0.2s, transform 0.15s;
        }

        .btn-ulozit:hover{
            background-color: #f5ede9;
            border-color: #cc1100;
            color: #cc1100;
            transform: translateY(-2px);
        }

        /* PRÁZDNÝ STAV */
        .empty-karta{
            text-align: center;
            padding: 48px 24px;
            color: #5b5a5a;
            font-size: 0.95rem;
            font-weight: 300;
            line-height: 1.8;
        }

        .empty-karta a{
            color: #cc1100;
            text-decoration: none;
        }

        .empty-karta a:hover{
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
        @media (max-width: 700px){
            .page-grid{
                grid-template-columns: 1fr;
            }

            .site-header{
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .slovo-hlavni{
                font-size: 1.6rem;
            }

            .rating-form{
                flex-direction: column;
            }

            .btn-rating{
                flex: none;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- HEADER -->
    <header class="site-header">
        <div class="header-left">
            <h1>Slovníček<span>.</span></h1>
            <p class="tagline">Zkoušení</p>
        </div>
        <nav class="header-right">
            <a href="index.php">Hlavní stránka</a>
            <a href="slovicka.php">Slovník</a>
            <a href="prekladac.php">Překladač</a>
            <a href="statistiky.php">Statistiky</a>
            <a href="balicek.php">Balíčky slov</a>
            <a href="profil.php">Profil</a>
        </nav>
    </header>

    <!-- HLAVNÍ GRID -->
    <div class="page-grid">

        <!-- LEVÝ PANEL -->
        <div class="panel">
            <h1>Zkoušení</h1>

            <div class="panel-sekce">
                <h4>Balíček</h4>
                <div class="btn-group">
                    <?php foreach(array_keys($starter_pack) as $kod){ ?>
                        <a href="zkouseni.php?lang=<?php echo $kod; ?>&mode=<?php echo $mode; ?>"
                           class="btn-filter <?php if ($vybrany_jazyk == $kod) { echo "aktivni"; } ?>">
                            <?php echo strtoupper($kod); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>

            <div class="panel-sekce">
                <h4>Mód</h4>
                <div class="btn-group">
                    <a href="zkouseni.php?lang=<?php echo $vybrany_jazyk; ?>&mode=flashcard"
                       class="btn-filter <?php if($mode == "flashcard") { echo "aktivni"; } ?>">
                        Kartičky
                    </a>
                    <a href="zkouseni.php?lang=<?php echo $vybrany_jazyk; ?>&mode=multiplechoice"
                       class="btn-filter <?php if($mode == "multiplechoice") { echo "aktivni"; } ?>">
                        Výběr z možností
                    </a>
                    <a href="zkouseni.php?lang=<?php echo $vybrany_jazyk; ?>&mode=doplnit"
                    class="btn-filter <?php if($mode == "doplnit") { echo "aktivni"; } ?>">
                        Doplňování
                    </a>
                    <a href="zkouseni.php?lang=<?php echo $vybrany_jazyk; ?>&mode=mujslovnik"
                       class="btn-filter <?php if($mode == "mujslovnik") { echo "aktivni"; } ?>">
                        Jen můj slovník
                    </a>
                    <a href="zkouseni.php?lang=<?php echo $vybrany_jazyk; ?>&mode=mujslovnik_doplnit"
                    class="btn-filter <?php if($mode == "mujslovnik_doplnit") { echo "aktivni"; } ?>">
                        Můj slovník + doplňování
                    </a>
                </div>
            </div>

            <div class="panel-sekce">
                <h4>Přesměrování</h4>
                <div class="btn-group">
                    <a href="statistiky.php" class="btn-filter">Statistiky</a>
                </div>
            </div>
        </div>

        <!-- PRAVÁ KARTA -->
        <div class="zkouseni-karta">
            <?php if($slovo == null){ ?>
                <?php if($ma_slovicka){ ?>
                    <p class="empty-karta">Dneska sis prozkoušel už všechna slovíčka z tvého slovníčku. Dej si pauzu!</p>
                    <p class="empty-karta">Jestli chceš stále procvičovat, zmáčkní tlačítko.</p>
                    <form method="post" style="text-align: center;">
                        <input type="hidden" name="action" value="reset_review">
                        <button type="submit" class="btn-odeslat">Procvičit znovu</button>
                    </form>
                <?php }else{ ?>
                    <p class="empty-karta">
                        V tomto jazyce zatím nemáš žádná slovíčka.<br>
                        Přidej si je přes <a href="prekladac.php">Překladač</a>, ručne je přidej přes <a href="slovicka.php">slovníček</a> nebo si některé ulož při zkoušení z balíčku.
                    </p>
                <?php } ?>
            <?php }else{ ?>
                <?php if($je_to_z_databaze){ ?>
                    <p class="zdroj-info">Zkouším tě z tvého slovníku...</p>
                <?php }else{ ?>
                    <p class="zdroj-info">Trénuješ z balíčku: <?php echo strtoupper($vybrany_jazyk); ?></p>
                    <?php if($mode != "mujslovnik" && $mode != "mujslovnik_doplnit"){ ?>
                    <div class="save-msg exists">
                        Všechna tvá slovíčka jsou vyčerpána, procvičuješ z výchozího balíčku.
                        <a href="balicek.php?lang=<?php echo $vybrany_jazyk; ?>">
                        Přidat slovíčka
                        </a>
                    </div>
                <?php } ?>
                <?php } ?>

                <?php if(isset($_SESSION["last_result"])){ ?>
                    <?php if($_SESSION["last_result"] == "spravne"){ ?>
                        <div class="feedback-banner spravne">Správně!</div>
                    <?php }elseif($_SESSION["last_result"] == "skoro"){ ?>
                        <div class="feedback-banner skoro">Nevadí!</div>
                    <?php }else{ ?>
                        <div class="feedback-banner spatne">Chyba!</div>
                    <?php } ?>
                    <?php unset($_SESSION["last_result"]); ?>
                <?php } ?>

                <?php if(isset($_SESSION["save_msg"])){ ?>
                    <?php if($_SESSION["save_msg"] == "saved"){ ?>
                        <div class="save-msg saved">Slovíčko bylo úspěšně uloženo do tvého slovníku!</div>
                    <?php }else{ ?>
                        <div class="save-msg exists">Tohle slovíčko už ve svém slovníku máš!</div>
                    <?php } ?>
                    <?php unset($_SESSION["save_msg"]); ?>
                <?php } ?>

                <div class="slovo-hlavni"><?php echo htmlspecialchars($slovo["translation"]); ?></div>

                <?php if($mode == "multiplechoice"){ ?>
                    <div class="choices">
                        <?php foreach($moznosti as $m){ ?>
                            <form method="post">
                                <input type="hidden" name="word_id" value="<?php echo $slovo["id"]; ?>">
                                <input type="hidden" name="vybrana_odpoved" value="<?php echo htmlspecialchars($m); ?>">
                                <?php if($m == $slovo["cz"]){ ?>
                                    <?php $vysledek = "green"; ?>
                                <?php }else{ ?>
                                    <?php $vysledek = "red"; ?>
                                <?php } ?>
                                <button type="submit" name="rating" value="<?php echo $vysledek; ?>" class="btn-choice">
                                    <?php echo htmlspecialchars($m); ?>
                                </button>
                            </form>
                        <?php } ?>
                    </div>

                    <?php if(!$je_to_z_databaze){ ?>
                        <?php ulozit_formular($slovo, $vybrany_jazyk); ?>
                    <?php } ?>

                <?php }elseif($mode == "doplnit" or $mode == "mujslovnik_doplnit"){ ?>
                    <?php if(isset($_SESSION["last_result_doplnit"])){ ?>
                        <div class="doplnit-spravna">
                            Tvá odpověď: <strong style="color:#aa2a1a"><?php echo htmlspecialchars($_SESSION["last_result_doplnit"]["zadana"]); ?></strong>
                            &nbsp;·&nbsp;
                            Správně: <strong><?php echo htmlspecialchars($_SESSION["last_result_doplnit"]["spravna"]); ?></strong>
                        </div>
                        <?php unset($_SESSION["last_result_doplnit"]); ?>
                    <?php } ?>

                    <form method="post" class="doplnit-form">
                        <input type="hidden" name="word_id" value="<?php echo $slovo['id']; ?>">
                        <input type="hidden" name="spravna_odpoved" value="<?php echo htmlspecialchars($slovo['cz']); ?>">
                        <input type="text" name="doplnit_odpoved" placeholder="Napiš překlad..." autofocus required>
                        <button type="submit" class="btn-odeslat">Potvrdit</button>
                    </form>

                <?php if(!$je_to_z_databaze){ ?>
                    <?php ulozit_formular($slovo, $vybrany_jazyk); ?>
                <?php } ?>

                <!-- MÓD FLASHCARD A MÓD MUJSLOVNIK -->
                <?php }else{ ?>
                    <details>
                        <summary>Ukázat překlad</summary>
                        <div class="details-obsah">
                            <h2><?php echo htmlspecialchars($slovo["cz"]); ?></h2>
                            <?php if ($vybrany_jazyk == "en" && !empty($slovo["vyznam"])) { ?>
                                <p class="vyznam"><?php echo htmlspecialchars($slovo["vyznam"]); ?></p>
                            <?php } ?>

                            <?php if($je_to_z_databaze){ ?>
                                <form method="post" class="rating-form">
                                    <input type="hidden" name="word_id" value="<?php echo htmlspecialchars($slovo["id"]); ?>">
                                    <button type="submit" name="rating" value="red"    class="btn-rating red">Nevěděl</button>
                                    <button type="submit" name="rating" value="yellow" class="btn-rating yellow">Na jazyku</button>
                                    <button type="submit" name="rating" value="green"  class="btn-rating green">Věděl</button>
                                </form>
                            <?php }else{ ?>
                                <?php ulozit_formular($slovo, $vybrany_jazyk); ?>
                            <?php } ?>
                        </div>
                    </details>
                <?php } ?>

            <?php } ?>

        </div>

    </div>

    <!-- PATIČKA -->
    <footer class="footer">
        <span>2026 Slovníček</span>
        <span>Made by Soukup Jakub</span>
    </footer>

</div>
</body>
</html>