<?php
session_start();
require 'dbconnect.php';

// KONTROLA, ZDA JE UŽIVATEL PŘIHLÁŠENÝ, POKUD NE, TAK HO PŘESMĚRUJEME NA prihlaseni.php
if (!isset($_SESSION["user_id"])) {
    header("Location: prihlaseni.php");
    exit();
}

// ZÍSKÁNÍ ID UŽIVATELE
$user_id = $_SESSION["user_id"];

// NAČTENÍ POČTU ODPOVĚDÍ PODLE HODNOCENÍ (green/yellow/red) PRO DNEŠNÍ DEN
$sql = "SELECT rating, 
        COUNT(*) as pocet 
        FROM history 
        WHERE user_id = '$user_id' AND DATE(date) = CURDATE() 
        GROUP BY rating";
$result = mysqli_query($conn, $sql);

$stats = ['green' => 0, 'yellow' => 0, 'red' => 0];
while ($row = mysqli_fetch_assoc($result)) {
    $stats[$row['rating']] = $row['pocet'];
}

$celkem = array_sum($stats);

// ZOBRAZENÍ POSLEDNÍCH POKUSŮ A JEJICH HODNOCENÍ (Věděl, Na jazyku, Nevěděl)
$sql = "SELECT h.rating, h.date, w.translation, w.cz, l.lang_name
        FROM history as h   
        JOIN words as w ON h.word_id = w.id
        JOIN languages as l ON w.lang_id = l.id
        WHERE h.user_id = '$user_id'
        AND DATE(h.date) = CURDATE()
        ORDER BY h.date DESC";
$posledni_result = mysqli_query($conn, $sql);

// ZOBRAZENÍ PĚTI SLOV, U KTERÝCH UŽIVATEL NEJVÍCE CHYBOVAL 
$sql = "SELECT w.cz, w.translation, l.lang_name,
        COUNT(*) as pocet_chyb
        FROM history as h
        JOIN words as w ON h.word_id = w.id
        JOIN languages as l ON w.lang_id = l.id
        WHERE h.user_id = '$user_id'
        AND h.rating IN ('red', 'yellow')
        GROUP BY h.word_id, w.cz, w.translation
        ORDER BY pocet_chyb DESC
        LIMIT 5";
$problemy_result = mysqli_query($conn, $sql);

// TÝDENNÍ AKTIVITA - POSLEDNÍCH 7 DNÍ
$sql = "SELECT DATE(date) as den, COUNT(*) as pocet
        FROM history
        WHERE user_id = '$user_id'
        AND date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(date)
        ORDER BY den ASC";
$aktivita_result = mysqli_query($conn, $sql);

// PŘÍPRAVA DAT PRO TÝDENNÍ GRAF (POSLEDNÍCH 7 DNÍ)
$tyden_data = [];
for($i = 6; $i >= 0; $i--){
    $den = date('Y-m-d', strtotime("-$i days"));
    $tyden_data[$den] = 0;
}
while($row = mysqli_fetch_assoc($aktivita_result)){
    $tyden_data[$row['den']] = (int)$row['pocet'];
}

// $tyden_max SLOUŽÍ JAKO ZÁKLAD PRO VÝPOČET VÝŠKY SLOUPCŮ
if(max($tyden_data) > 0){ // MINIMÁLNĚ 1, ABY NEDOŠLO K DĚLENÍ 0
    $tyden_max = max($tyden_data);
}else{
    $tyden_max = 1;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Moje statistiky</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after{
            margin: 0;
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
            padding: 32px;
        }

        .panel h1{
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.4px;
            text-transform: uppercase;
            color: #1c1c1c;
            margin-bottom: 16px;
        }

        .panel h2{
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #888;
            margin: 26px 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #d0cdc7;
        }

        /* BOXY */
        .box{
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
            padding: 18px;
            margin: 0 10px 10px 0;
            border-radius: 14px;
            color: #1c1c1c;
            background-color: #e2e0db;
            border: 1px solid #d0cdc7;
            min-width: 170px;
            font-size: 0.9rem;
        }

        .box strong{
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            display: block;
        }

        .box.green{
            border-left: 4px solid #2a7a47;
        }

        .box.yellow{
            border-left: 4px solid #7a6a1a;
        }

        .box.red{
            border-left: 4px solid #aa2a1a;
        }

        .box.neutral{
            border-left: 4px solid #888;
        }

        .box.green strong{
            color: #2a7a47;
        }

        .box.yellow strong{
            color: #7a6a1a;
        }

        .box.red strong{
            color: #aa2a1a;
        }

        .box.neutral strong{
            color: #444;
        }

        /* TABULKY */
        table{
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
            margin-top: 10px;
        }

        th{
            text-align: left;
            padding: 10px 14px;
            color: #aaa;
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #d0cdc7;
        }

        td{
            padding: 14px;
            color: #1c1c1c;
            border-bottom: 1px solid #e2e0db;
            vertical-align: top;
        }

        tbody tr:last-child td{
            border-bottom: none;
        }

        tbody tr:hover td{
            background-color: #e8e5e0;
        }

        .tabulka-scroll {
            max-height: 550px;
            overflow-y: auto;
            border-radius: 10px;
            border: 1px solid #d0cdc7;
        }

        .tabulka-scroll thead th {
            position: sticky;
            top: 0;
            background-color: #eeece8;
            z-index: 1;
        }


        .badge{
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge.green{
            background-color: #edf7f0;
            color: #2a7a47;
        }

        .badge.yellow{
            background-color: #fdf8e8;
            color: #7a6a1a;
        }

        .badge.red{
            background-color: #fdf0ee;
            color: #aa2a1a;
        }

        .empty-text{
            color: #888;
            font-size: 0.95rem;
            font-weight: 300;
            padding: 12px 0;
        }

        .empty-text a{
            color: #cc1100;
            text-decoration: none;
        }

        .empty-text a:hover{
            text-decoration: underline;
        }

        /* GRAF */
        .graf{
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 4px;
        }

        .graf-karta{
            background-color: #e2e0db;
            border: 1px solid #d0cdc7;
            border-radius: 12px;
            padding: 20px;
        }

        .graf-karta h4{
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #aaa;
            margin-bottom: 16px;
        }

        /* SLOUPCOVÝ GRAF */
        .sloupce{
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 100px;
        }

        .sloupec-wrap{
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            flex: 1;
        }

        .sloupec{
            width: 100%;
            background-color: #cc1100;
            border-radius: 4px 4px 0 0;
            min-height: 3px;
            transition: opacity 0.2s;
        }

        .sloupec:hover{
            opacity: 0.75;
        }

        .sloupec-label{
            font-size: 0.65rem;
            color: #aaa;
            text-align: center;
        }

        @media (max-width: 620px){
            .graf{
                grid-template-columns: 1fr;
            }
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
            .panel{
                padding: 20px 16px;
            }

            .site-header{
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .box{
                min-width: unset;
                width: 100%;
                margin-right: 0;
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
            <p class="tagline">Statistiky</p>
        </div>
        <nav class="header-right">
            <a href="index.php">Hlavní stránka</a>
            <a href="slovicka.php">Slovník</a>
            <a href="prekladac.php">Překladač</a>
            <a href="zkouseni.php">Zkoušení</a>
            <a href="balicek.php">Balíčky slov</a>
            <a href="profil.php">Profil</a>
        </nav>
    </header>

    <!-- PANEL -->
    <div class="panel">

        <?php if($celkem == 0){ ?>
            <p class="empty-text">Dnes sis ještě nezkoušel. <a href="zkouseni.php">Jdi na zkoušení!</a></p>
        <?php }else{ ?>
            <h1>Dnešní přehled</h1>

            <div class="box green"><span>Věděl</span><strong><?php echo $stats["green"]; ?></strong></div>
            <div class="box yellow"><span>Na jazyku</span><strong><?php echo $stats["yellow"]; ?></strong></div>
            <div class="box red"><span>Nevěděl</span><strong><?php echo $stats["red"]; ?></strong></div>
            <div class="box neutral"><span>Celkem</span><strong><?php echo $celkem; ?></strong></div>

            <h2>Grafický přehled</h2>
                <!-- SLOUPCOVÝ GRAF — týdenní aktivita -->
                <div class="graf-karta">
                    <h4>Aktivita za posledních 7 dní</h4>
                    <div class="sloupce">
                        <?php foreach ($tyden_data as $den => $pocet) { ?>
                            <div class="sloupec-wrap">
                                <div class="sloupec"
                                    style="height: <?php echo round($pocet / $tyden_max * 100); ?>px"
                                    title="<?php echo $pocet; ?> pokusů"></div>
                                <span class="sloupec-label"><?php echo date('d.m', strtotime($den)); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>

            <h2>Poslední pokusy</h2>
            <div class="tabulka-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Slovíčko</th>
                            <th>Překlad</th>
                            <th>Hodnocení</th>
                            <th>Jazyk</th>
                            <th>Čas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($posledni_result)){ ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["translation"]); ?></td>
                                <td><?php echo htmlspecialchars($row["cz"]); ?></td>
                                <td>
                                    <?php if ($row["rating"] == "green") { ?>
                                        <span class="badge green">Věděl</span>
                                    <?php } elseif ($row["rating"] == "yellow") { ?>
                                        <span class="badge yellow">Na jazyku</span>
                                    <?php } else { ?>
                                        <span class="badge red">Nevěděl</span>
                                    <?php } ?>
                                </td>
                                <td><?php echo htmlspecialchars($row["lang_name"]); ?></td>
                                <td><?php echo date("j. n. Y H:i", strtotime($row["date"])); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

        <h2>Největší problémy</h2>
        
        <?php if(mysqli_num_rows($problemy_result) == 0){ ?>
            <p class="empty-text">Zatím žádné chyby.</p>
        <?php }else{ ?>
        <table>
            <thead>
                <tr>
                    <th>Slovíčko</th>
                    <th>Překlad</th>
                    <th>Jazyk</th>
                    <th>Počet chyb</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($problemy_result)){ ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["translation"]); ?></td>
                        <td><?php echo htmlspecialchars($row["cz"]); ?></td>
                        <td><?php echo htmlspecialchars($row["lang_name"]); ?></td>
                        <td><?php echo $row["pocet_chyb"]; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php } ?>

    </div>

    <!-- PATIČKA -->
    <footer class="footer">
        <span>2026 Slovníček</span>
        <span>Made by Jakub Soukup</span>
    </footer>

</div>
</body>
</html>