<?php
session_start();
require 'dbconnect.php';

// KONTROLA, ZDA JE UŽIVATEL PŘIHLÁŠENÝ, POKUD NE, TAK HO PŘESMĚRUJEME NA prihlaseni.php
if(!isset($_SESSION["user_id"])){
    header("Location: prihlaseni.php");
    exit();
}

// ZÍSKÁNÍ ID UŽIVATELE 
$user_id = $_SESSION["user_id"];

// PŘIDÁNÍ SLOVÍČKA RUČNĚ
if(isset($_POST["pridat_rucne"])){
    $cz = ucfirst(strtolower(trim($_POST["cz"])));
    $cz = mysqli_real_escape_string($conn, $cz);
    $translation = ucfirst(strtolower(trim($_POST["translation"])));
    $translation = mysqli_real_escape_string($conn, $translation);
    $lang_id = $_POST["lang_id"];

    if($cz !== "" && $translation !== "" && $lang_id > 0){
        // KONTROLA DUPLICITY
        $sql = "SELECT id FROM words 
                WHERE cz = '$cz' 
                AND lang_id = '$lang_id' 
                AND user_id = '$user_id'";
        $result = mysqli_query($conn, $sql);
        
        if(mysqli_num_rows($result) == 0){
            $sql = "INSERT INTO words (cz, translation, vyznam, lang_id, user_id) 
                    VALUES ('$cz', '$translation', '', '$lang_id', '$user_id')";
            mysqli_query($conn, $sql);
            header("Location: slovicka.php?zprava=ulozeno");
            exit();
        }
    }
    header("Location: slovicka.php?chyba=duplicita");
    exit();
}

// NAČTENÍ JAZYKŮ PRO FORMULÁŘ
$sql = "SELECT * FROM languages ORDER BY lang_code ASC";
$result = mysqli_query($conn, $sql);
$jazyky = [];
while($row = mysqli_fetch_assoc($result)){
    $jazyky[] = $row;
}

// NAČTENÍ VŠECH SLOVÍČEK Z DATABÁZE, SPOJENÍ TABULEK words A languages,
// VÝSLEDEK ROZDĚLÍ DO POLE PODLE JAZYKA: $slovnik["en"][], $slovnik["de"][]...
$sql = "SELECT words.id, words.cz, words.translation, words.vyznam, languages.lang_code 
        FROM words 
        JOIN languages ON words.lang_id = languages.id 
        WHERE words.user_id = '$user_id' 
        ORDER BY words.cz ASC";
$result = mysqli_query($conn, $sql);
$slovnik = [];
while($row = mysqli_fetch_assoc($result)){
    $jazyk = $row["lang_code"];
    $slovnik[$jazyk][] = $row;
}

// FILTR, "vse" JE DEFAULT
if(isset($_GET["jazyk"])){
    $aktivni_jazyk = $_GET["jazyk"];
}else{
    $aktivni_jazyk = "vse";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Můj Slovníček</title>

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
            max-width: 960px;
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
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        h2{
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.4px;
            text-transform: uppercase;
            color: #1c1c1c;
            margin-bottom: 4px;
        }

        h3{
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #d0cdc7;
        }

        .vitej{
            color: #888;
            font-size: 0.9rem;
            font-weight: 300;
            margin-bottom: 4px;
        }

        .link{
            color: #cc1100;
            text-decoration: none;
            transition: color 0.2s;
        }

        .link:hover {
            text-decoration: underline;
        }

        /* FILTR TLAČÍTKA */
        .lang-filter{
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .btn-filter{
            display: inline-block;
            padding: 8px 20px;
            background-color: #e2e0db;
            color: #888;
            border: 1px solid #d0cdc7;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
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

        /* FORMULÁŘ PŘIDAT */
        .form-pridat{
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .form-pridat input, .form-pridat select{
            padding: 9px 14px;
            border: 1px solid #d0cdc7;
            border-radius: 10px;
            background-color: #e2e0db;
            font-family: 'Roboto', sans-serif;
            font-size: 0.88rem;
            color: #1c1c1c;
            outline: none;
            flex: 1;
            min-width: 130px;
        }

        .form-pridat input:focus, .form-pridat select:focus{
            border-color: #cc1100;
        }

        .btn-pridat{
            padding: 9px 20px;
            background-color: #cc1100;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background-color 0.2s;
            white-space: nowrap;
        }

        .btn-pridat:hover{
            background-color: #aa0e00;
        }

        /* TABULKA */
        table{
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        th{
            background-color: transparent;
            color: #aaa;
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid #d0cdc7;
        }

        td{
            padding: 14px;
            color: #1c1c1c;
            text-align: left;
            border-bottom: 1px solid #e2e0db;
        }

        tbody tr:last-child td{
            border-bottom: none;
        }

        tbody tr:hover td{
            background-color: #e8e5e0;
        }

        .definice-text{
            color: #aaa;
            font-style: italic;
            font-size: 0.85em;
        }

        .btn-smazat{
            display: inline-block;
            padding: 6px 14px;
            background-color: transparent;
            color: #cc1100;
            border: 1px solid #e8c8c3;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            font-family: 'Roboto', sans-serif;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
        }

        .btn-smazat:hover{
            background-color: #cc1100;
            color: #ffffff;
            border-color: #cc1100;
        }

        .empty-slovnik{
            text-align: center;
            padding: 48px 24px;
            color: #bbb;
            font-size: 0.95rem;
            font-weight: 300;
            line-height: 1.6;
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
            .panel{
                padding: 20px 16px;
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

    <!-- HEADER -->
    <header class="site-header">
        <div class="header-left">
            <h1>Slovníček<span>.</span></h1>
            <p class="tagline">Můj Slovníček</p>
        </div>
        <nav class="header-right">
            <a href="index.php">Hlavní stránka</a>
            <a href="prekladac.php">Překladač</a>
            <a href="zkouseni.php">Zkoušení</a>
            <a href="statistiky.php">Statistiky</a>
            <a href="balicek.php">Balíčky slov</a>
            <a href="profil.php">Profil</a>
        </nav>
    </header>


    <!-- HLAVNÍ PANEL -->
    <div class="panel">
        <h2>Moje Uložená Slovíčka</h2>
        <p class="vitej">Zde najdeš všechna slovíčka, která sis uložil. Definice jsou dostupné pouze pro angličtinu.</p>
        <p class="vitej">Ukládej pouze slovíčka, která dávají smysl. Za svůj slovníček zodpovídáš ty.</p>

        <!-- FORMULÁŘ PŘIDAT RUČNĚ -->
        <form method="POST" action="slovicka.php" class="form-pridat">
            <input type="text" name="cz" placeholder="Česky" required>
            <input type="text" name="translation" placeholder="Překlad" required>
            <select name="lang_id" required>
                <option value="">Jazyk</option>
                <?php foreach($jazyky as $j){ ?>
                    <?php if($j["lang_code"] != "cs"){ ?>
                        <option value="<?php echo $j["id"]; ?>"><?php echo strtoupper($j["lang_code"]); ?></option>
                    <?php } ?>
                <?php } ?>
            </select>

            <button type="submit" name="pridat_rucne" class="btn-pridat">Přidat</button>
        </form>

        <?php if(isset($_GET["chyba"]) && $_GET["chyba"] == "duplicita"){ ?>
        <p style="color:#cc1100; font-size:0.9rem;">Toto slovíčko už máš uložené.</p>
        <?php }elseif(isset($_GET["zprava"]) && $_GET["zprava"] == "ulozeno"){ ?>
        <p style="color:#2a7a2a; font-size:0.9rem;">Slovíčko bylo úspěšně uloženo.</p>
        <?php } ?>
        
        <?php if(empty($slovnik)){ ?>
            <p class="empty-slovnik">Zatím nemáš uložená žádná slovíčka.</p>
        <?php }else{ ?>

            <!-- FILTR TLAČÍTKA -->
            <div class="lang-filter">
                <a href="slovicka.php" class="btn-filter <?php if ($aktivni_jazyk == "vse"){ echo "aktivni";} ?>">
                Vše</a>
                <?php foreach($slovnik as $kod_jazyka => $slovicka){ ?>
                    <a href="slovicka.php?jazyk=<?php echo $kod_jazyka; ?>"
                        class="btn-filter <?php if($aktivni_jazyk == $kod_jazyka){ echo "aktivni"; } ?>">
                        <?php echo strtoupper($kod_jazyka); ?>
                    </a>
                <?php } ?>
            </div>

            <!-- SEKCE JAZYKŮ -->
            <?php foreach($slovnik as $kod_jazyka => $slovicka){ ?>
                <?php if($aktivni_jazyk == "vse" or $aktivni_jazyk == $kod_jazyka){ ?>
                    <h3>Jazyk: <?php echo strtoupper($kod_jazyka); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Česky</th>
                                <th>Překlad (<?php echo strtoupper($kod_jazyka); ?>)</th>
                                <?php if ($kod_jazyka == "en") { ?>
                                    <th>Anglická definice</th>
                                <?php } ?>
                                <th>Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($slovicka as $slovo){ ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($slovo["cz"]); ?></td>
                                    <td><?php echo htmlspecialchars($slovo["translation"]); ?></td>
                                    <?php if ($kod_jazyka == "en") { ?>
                                        <td class="definice-text">
                                            <?php if($slovo["vyznam"] != ""){ ?>
                                                <?php echo htmlspecialchars($slovo["vyznam"]); ?>
                                            <?php }else{ ?>
                                                ---
                                            <?php } ?>
                                        </td>
                                    <?php } ?>
                                    <td>
                                        <a href="smazat.php?id=<?php echo $slovo["id"]; ?>&jazyk=<?php echo $kod_jazyka; ?>"
                                        class="btn-smazat"
                                        onclick="return confirm('Opravdu smazat?')">Smazat</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                <?php } ?>
            <?php } ?>

        <?php } ?>
    </div>

    <!-- PATIČKA -->
    <footer class="footer">
        <span>2026 Slovníček</span>
        <span>Made by Soukup Jakub</span>
    </footer>

</div>

</body>
</html>
