<?php
session_start();
require 'dbconnect.php';
require 'starterpack.php';

// KONTROLA, ZDA JE UŽIVATEL PŘIHLÁŠENÝ, POKUD NE, TAK HO PŘESMĚRUJEME NA prihlaseni.php
if (!isset($_SESSION["user_id"])) {
    header("Location: prihlaseni.php");
    exit();
}

// ZÍSKÁNÍ ID UŽIVATELE
$user_id = $_SESSION["user_id"];

// VÝBĚR JAZYKA, DEFAULTNĚ "en"
$vybrany_jazyk = "en";
if (isset($_GET["lang"]) && isset($starter_pack[$_GET["lang"]])) {
    $vybrany_jazyk = $_GET["lang"];
}
$balicek = $starter_pack[$vybrany_jazyk];

// PŘEKLAD LANG_CODE ("en") NA ČÍSELNÉ LANG_ID Z DATABÁZE
$sql = "SELECT id FROM languages WHERE lang_code = '$vybrany_jazyk'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$lang_id = $row["id"];

// ULOŽENÍ JEDNOHO SLOVÍČKA
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
        $_SESSION["save_msg"] = "exists";
    }else{
        $sql = "INSERT INTO words (user_id, cz, translation, vyznam, lang_id, level, next_review)
                VALUES ('$user_id', '$cz', '$translation', '$vyznam', '$lang_id', 1, NOW())";
        mysqli_query($conn, $sql);
        $_SESSION["save_msg"] = "saved";
    }

    header("Location: balicek.php?lang=$vybrany_jazyk");
    exit();
}

// ULOŽENÍ VŠECH SLOVÍČEK
if(isset($_POST["action"]) && $_POST["action"] == "ulozit_vse"){
    $ulozeno = 0;

    foreach($balicek as $slovo){
        $cz = mysqli_real_escape_string($conn, $slovo["cz"]);
        $translation = mysqli_real_escape_string($conn, $slovo["translation"]);
        $vyznam = "";
        if($vybrany_jazyk == 'en' && isset($slovo["vyznam"])){
            $vyznam = mysqli_real_escape_string($conn, $slovo["vyznam"]);
        }

        // KONTROLA DUPLICITY, POKUD SLOVO JE UŽ V DB, TAK HO IGNORUJEME
        $sql = "SELECT id FROM words
                WHERE user_id = '$user_id' AND cz = '$cz' AND lang_id = '$lang_id'";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) == 0){
            $sql = "INSERT INTO words (user_id, cz, translation, vyznam, lang_id, level, next_review)
                    VALUES ('$user_id', '$cz', '$translation', '$vyznam', '$lang_id', 1, NOW())";
            mysqli_query($conn, $sql);
            $ulozeno++;
        }
    }
    $_SESSION["save_msg"] = "vse_$ulozeno";

    header("Location: balicek.php?lang=$vybrany_jazyk");
    exit();
}

// NAČTENÍ SLOV, KTERÁ UŽ MÁ UŽIVATEL ULOŽENÁ
$sql = "SELECT cz FROM words WHERE user_id = '$user_id' AND lang_id = '$lang_id'";
$result = mysqli_query($conn, $sql);
$ulozena_slova = [];
while($row = mysqli_fetch_assoc($result)){
    $ulozena_slova[] = $row["cz"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Balíček slovíček</title>

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

        /* PANEL */
        .panel{
            background-color: #eeece8;
            border: 1px solid #d0cdc7;
            border-radius: 16px;
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .panel-row{
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .panel-sekce{
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .panel-sekce h4{
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #aaa;
        }

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

        .btn-filter:hover, .btn-filter.aktivni{
            background-color: #f5ede9;
            color: #cc1100;
            border-color: #cc1100;
        }

        /* ZPRÁVY */
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

        .save-msg.vse{
            background-color: #edf7f0;
            border: 1px solid #a8d5b5;
            color: #2a7a47;
        }

        /* TABULKA */
        .tabulka-karta{
            background-color: #eeece8;
            border: 1px solid #d0cdc7;
            border-radius: 16px;
            overflow: hidden;
        }

        .tabulka-hlavicka{
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid #d0cdc7;
        }

        .tabulka-hlavicka h2{
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1c1c1c;
        }

        .tabulka-hlavicka .pocet{
            font-size: 0.82rem;
            color: #aaa;
            font-weight: 400;
        }

        .btn-ulozit-vse{
            padding: 10px 22px;
            background-color: #1c1c1c;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.15s;
        }

        .btn-ulozit-vse:hover{
            background-color: #cc1100;
        }

        table{
            width: 100%;
            border-collapse: collapse;
        }

        thead th{
            padding: 12px 20px;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #aaa;
            background-color: #e8e5e0;
            border-bottom: 1px solid #d0cdc7;
        }

        tbody tr{
            border-bottom: 1px solid #e0ddd8;
            transition: background-color 0.15s;
        }

        tbody tr:last-child{
            border-bottom: none;
        }

        tbody tr:hover{
            background-color: #e8e5e0;
        }

        tbody td{
            padding: 14px 20px;
            font-size: 0.92rem;
            vertical-align: middle;
        }

        td.slovo-cz{
            font-weight: 600;
            color: #1c1c1c;
        }

        td.slovo-translation{
            color: #444;
        }

        td.slovo-vyznam{
            color: #999;
            font-style: italic;
            font-size: 0.83rem;
            font-weight: 300;
        }

        td.akce{
            text-align: right;
            white-space: nowrap;
        }

        .badge-ulozeno{
            display: inline-block;
            padding: 5px 12px;
            background-color: #edf7f0;
            border: 1px solid #a8d5b5;
            color: #2a7a47;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .btn-ulozit{
            padding: 7px 16px;
            background-color: #eeece8;
            color: #1c1c1c;
            border: 1px solid #d0cdc7;
            border-radius: 8px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s, color 0.2s, transform 0.15s;
        }

        .btn-ulozit:hover{
            background-color: #f5ede9;
            border-color: #cc1100;
            color: #cc1100;
        }

        /* PATIČKA */
        .footer{
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #999;
        }

        @media (max-width: 700px){
            .site-header{
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .panel-row{
                flex-direction: column;
                align-items: flex-start;
            }

            td.slovo-vyznam{
                display: none;
            }

            thead th.col-vyznam{
                display: none;
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
            <p class="tagline">Balíček slovíček</p>
        </div>
        <nav class="header-right">
            <a href="index.php">Hlavní stránka</a>
            <a href="prekladac.php">Překladač</a>
            <a href="slovicka.php">Slovník</a>
            <a href="zkouseni.php">Zkoušení</a>
            <a href="statistiky.php">Statistiky</a>
            <a href="profil.php">Profil</a>
        </nav>
    </header>

    <!-- ZPRÁVA -->
    <?php if(isset($_SESSION["save_msg"])){ ?>
        <?php $msg = $_SESSION["save_msg"]; ?>
        <?php if($msg == "saved"){ ?>
            <div class="save-msg saved">Slovíčko bylo úspěšně uloženo do tvého slovníku!</div>
        <?php }elseif($msg == "exists"){ ?>
            <div class="save-msg exists">Tohle slovíčko už ve svém slovníku máš!</div>
        <?php }elseif(str_starts_with($msg, "vse_")){ ?>
            <?php $pocet = substr($msg, 4); ?>
            <div class="save-msg vse">
                <?php if($pocet > 0){ ?>
                    Uloženo <?php echo $pocet; ?> nových slovíček!
                <?php }else{ ?>
                    Všechna slovíčka z tohoto balíčku už máš uložena.
                <?php } ?>
            </div>
        <?php } ?>
        <?php unset($_SESSION["save_msg"]); ?>
    <?php } ?>

    <!-- PANEL S FILTREM -->
    <div class="panel">
        <div class="panel-row">
            <div class="panel-sekce">
                <h4>Jazyk:</h4>
                    <?php foreach(array_keys($starter_pack) as $kod){ ?>
                        <a href="balicek.php?lang=<?php echo $kod; ?>"
                        class="btn-filter <?php if ($vybrany_jazyk == $kod) echo 'aktivni'; ?>">
                            <?php echo strtoupper($kod); ?>
                        </a>
                    <?php } ?>
            </div>
            <a href="zkouseni.php?lang=<?php echo $vybrany_jazyk; ?>" class="btn-filter">
                Procvičovat tento balíček
            </a>
        </div>
    </div>

    <!-- TABULKA -->
    <div class="tabulka-karta">
        <div class="tabulka-hlavicka">
            <div>
                <h2>Balíček <?php echo strtoupper($vybrany_jazyk); ?></h2>
                <span class="pocet"><?php echo count($balicek); ?> slovíček</span>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="ulozit_vse">
                <button type="submit" class="btn-ulozit-vse">Uložit vše do slovníku</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Česky</th>
                    <th><?php echo strtoupper($vybrany_jazyk); ?></th>
                    <?php if($vybrany_jazyk == "en"){ ?>
                        <th class="col-vyznam">Význam</th>
                    <?php } ?>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($balicek as $i => $slovo){ ?>
                    <tr>
                        <td style="color:#ccc; font-size:0.8rem; width:36px;"><?php echo $i + 1; ?></td>
                        <td class="slovo-cz"><?php echo htmlspecialchars($slovo["cz"]); ?></td>
                        <td class="slovo-translation"><?php echo htmlspecialchars($slovo["translation"]); ?></td>

                        <?php if($vybrany_jazyk == "en"){ ?>
                            <td class="slovo-vyznam"><?php if(isset($slovo["vyznam"])){ echo htmlspecialchars($slovo["vyznam"]); } ?></td>
                        <?php } ?>

                        <td class="akce">
                            <?php if(in_array($slovo["cz"], $ulozena_slova)){ ?>
                                <span class="badge-ulozeno">Uloženo</span>
                            <?php }else{ ?>

                                <form method="post">
                                    <input type="hidden" name="action" value="ulozit_do_db">
                                    <input type="hidden" name="cz" value="<?php echo htmlspecialchars($slovo["cz"]); ?>">
                                    <input type="hidden" name="translation" value="<?php echo htmlspecialchars($slovo["translation"]); ?>">

                                    <?php if($vybrany_jazyk == "en"){ ?>
                                        <input type="hidden" name="vyznam" value="<?php
                                        if(isset($slovo["vyznam"])){
                                            echo htmlspecialchars($slovo["vyznam"]);
                                        }
                                        ?>">
                                    <?php } ?>

                                    <input type="hidden" name="lang_code" value="<?php echo htmlspecialchars($vybrany_jazyk); ?>">
                                    <button type="submit" class="btn-ulozit">Uložit</button>
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- PATIČKA -->
    <footer class="footer">
        <span>2026 Slovníček</span>
        <span>Made by Soukup Jakub</span>
    </footer>

</div>
</body>
</html>
