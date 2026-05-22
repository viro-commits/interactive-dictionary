<?php
session_start();

// CITÁTY
$citaty = ["Každé nové slovo je nový svět.", "Opakování je matka moudrosti.", "Investice do vědění nese nejlepší úrok.", "Kořeny vzdělání jsou hořké, ale ovoce je sladké.", "Pro život, ne pro školu se učíme.", "Nejlepší část vzdělání je ta, kterou člověk získal sám."];
$nahodny_citat = $citaty[array_rand($citaty)];
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Můj Interaktivní Slovníček</title>
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
            grid-template-columns: 1.3fr 0.7fr;
            gap: 16px;
        }

        /* LEVÝ PANEL */
        .panel{
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

        .panel .citat-block{
            border-left: 3px solid #cc1100;
            padding-left: 14px;
            margin-top: 28px;
        }

        .panel .citat-block p{
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

        /* PRAVÝ NAV GRID */
        .nav-grid{
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-label{
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #999;
            padding: 0 4px;
            margin-bottom: 2px;
        }

        .btn{
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 16px;
            background-color: #eeece8;
            color: #1c1c1c;
            text-decoration: none;
            border-radius: 10px;
            border: 1px solid #d0cdc7;
            font-weight: 500;
            font-size: 0.92rem;
            transition: border-color 0.2s, background-color 0.2s, transform 0.15s;
            cursor: pointer;
        }

        .btn::before{
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #cc1100;
            flex-shrink: 0;
        }

        .btn:hover{
            border-color: #cc1100;
            background-color: #f5ede9;
        }

        .btn-logout{
            color: #cc1100;
            background-color: #f5ede9;
            border-color: #e8c8c3;
            margin-top: auto;
        }

        .btn-logout::before{
            display: none;
        }

        .btn-logout:hover{
            background-color: #cc1100;
            color: #ffffff;
            border-color: #cc1100;
            transform: none;
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
        @media (max-width: 560px){
            .page-grid{
                grid-template-columns: 1fr;
            }

            .site-header h1{
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- NADPIS -->
    <header class="site-header">
        <h1>Slovníček<span>.</span></h1>
        <p class="tagline">Tvůj osobní interaktivní slovník</p>
    </header>

    <div class="page-grid">

        <!-- LEVÝ PANEL -->
        <div class="panel">
            <div>
                <?php if(isset($_SESSION["username"])){ ?>
                    <p class="vitej">Ahoj, 
                    <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!</p>
                    <p class="about-block">Vítej zpět! Pokračuj ve studování slovíček vlastním tempem.</p>
                <?php }else{ ?>
                    <p class="vitej">Vítej v Interaktivním Slovníčku</p>
                    <p class="about-block">Pro práci s vlastním slovníčkem se prosím registruj nebo přihlaš.</p>
                <?php } ?>

                <div class="citat-block">
                    <p><?php echo htmlspecialchars($nahodny_citat); ?></p>
                </div>
            </div>

            <div class="nazev-block">Interaktivní Slovníček</div>
        </div>

        <!-- PRAVÝ NAV GRID -->
        <nav class="nav-grid">
            <?php if(isset($_SESSION["username"])){ ?>
                <span class="nav-label">Nástroje</span>
                <a href="prekladac.php" class="btn">Překladač</a>
                <a href="slovicka.php" class="btn">Slovník</a>
                <a href="zkouseni.php" class="btn">Zkoušení</a>
                <a href="statistiky.php" class="btn">Statistiky</a>
                <a href="balicek.php" class="btn">Balíčky slov</a>
                <a href="profil.php" class="btn">Profil</a>
                <a href="logout.php" class="btn btn-logout">Odhlásit se</a>
            <?php }else{ ?>
                <span class="nav-label">Účet</span>
                <a href="prihlaseni.php" class="btn">Přihlášení</a>
                <a href="registrace.php" class="btn">Registrace</a>
                <span class="nav-label">Nástroje</span>
                <a href="prekladac.php" class="btn">Překladač (host)</a>
            <?php } ?>
        </nav>

    </div>

    <!-- PATIČKA -->
    <footer class="footer">
        <span>2026 Slovníček</span>
        <span>Made by Soukup Jakub</span>
    </footer>

</div>
</body>
</html>
