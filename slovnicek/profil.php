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

// ZPRÁVY PRO UŽIVATELE (NAPŘ. SLOVÍČKO BYLO SMAZÁNO)
$zprava = "";
$zprava_typ = "ok";

// NAČTENÍ username Z DB
$sql = "SELECT username FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// POČET SLOVÍČEK PODLE JAZYKA
// VÝSLEDEK JE SESKUPÍ PODLE LANG_CODE
$sql = "SELECT l.lang_code, COUNT(*) as pocet
        FROM words w
        JOIN languages l ON w.lang_id = l.id
        WHERE w.user_id = '$user_id'
        GROUP BY l.lang_code";
$result = mysqli_query($conn, $sql);

$slovicka_podle_jazyka = [];
$celkem_slovicek = 0;
while($row = mysqli_fetch_assoc($result)){
    $slovicka_podle_jazyka[$row['lang_code']] = $row['pocet'];
    $celkem_slovicek += $row["pocet"];
}

// SMAZÁNÍ SLOVÍČEK PODLE JAZYKA
if(isset($_POST["smazat_jazyk"])){
    $lang_code = mysqli_real_escape_string($conn, $_POST["lang_code"]);

    $sql = "SELECT id FROM languages WHERE lang_code = '$lang_code'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if($row){
        $lang_id = $row["id"];

        // SMAZÁNÍ HISTORIE PRO TATO SLOVÍČKA
        $sql = "DELETE h FROM history h
                JOIN words w ON h.word_id = w.id
                WHERE w.user_id = '$user_id' AND w.lang_id = '$lang_id'";
        mysqli_query($conn, $sql);

        // SMAZÁNÍ SLOVÍČEK
        $sql = "DELETE FROM words 
                WHERE user_id = '$user_id' AND lang_id = '$lang_id'";
        mysqli_query($conn, $sql);

        $zprava = "Slovíčka v jazyce " . strtoupper($lang_code) . " byla smazána.";

        // AKTUALIZACE POLE PO SMAZÁNÍ
        unset($slovicka_podle_jazyka[$lang_code]); // ODSTRANÍ KÓDY JAZYKŮ Z POLE
        $celkem_slovicek = array_sum($slovicka_podle_jazyka);
    }
}

// SMAZÁNÍ VŠECH SLOVÍČEK
if(isset($_POST["smazat_vsechna"])){
    $sql = "DELETE FROM history WHERE user_id = '$user_id'";
    mysqli_query($conn, $sql);

    $sql = "DELETE FROM words WHERE user_id = '$user_id'";
    mysqli_query($conn, $sql);

    $zprava = "Všechna slovíčka byla smazána.";
    $slovicka_podle_jazyka = [];
    $celkem_slovicek = 0;
}

// SMAZÁNÍ ÚČTU
if(isset($_POST["smazat_ucet"])){
    $heslo = $_POST["heslo_potvrzeni"];

    $sql = "SELECT password FROM users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if(password_verify($heslo, $row["password"])){
        $sql = "DELETE FROM history WHERE user_id = '$user_id'";
        mysqli_query($conn, $sql);

        $sql = "DELETE FROM words WHERE user_id = '$user_id'";
        mysqli_query($conn, $sql);

        $sql = "DELETE FROM users WHERE id = '$user_id'";
        mysqli_query($conn, $sql);

        session_destroy();
        header("Location: index.php");
        exit();
    }else{
        $zprava = "Špatné heslo, účet nebyl smazán.";
        $zprava_typ = "chyba";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profil</title>
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

        .panel h2{
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin: 20px 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #d0cdc7;
        }

        .panel h2:first-child{
            margin-top: 0;
        }

        /* INFO ŘÁDKY */
        .info-row{
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e0db;
            font-size: 0.9rem;
        }

        .info-row .label{
            color: #888; font-weight: 500;
        }

        .info-row .value{
            color: #1c1c1c; font-weight: 600;
        }

        /* BOXY JAZYKŮ */
        .lang-boxy{
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .lang-box{
            background-color: #e2e0db;
            border: 1px solid #d0cdc7;
            border-radius: 12px;
            padding: 16px 20px;
            min-width: 120px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .lang-box .lang-kod{
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #aaa;
        }

        .lang-box .lang-pocet{
            font-size: 1.6rem;
            font-weight: 800;
            color: #1c1c1c;
            letter-spacing: -0.5px;
        }

        .lang-box .lang-label{
            font-size: 0.78rem;
            color: #888;
        }

        /* ZPRÁVA */
        .zprava{
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 500;
            background-color: #edf7f0;
            border: 1px solid #a8d5b5;
            color: #2a7a47;
        }

        .zprava.chyba{
            background-color: #fdf0ee;
            border: 1px solid #e8b8b3;
            color: #aa2a1a;
        }

        /* TLAČÍTKA */
        .btn{
            display: inline-block;
            padding: 10px 22px;
            border-radius: 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            border: 1px solid;
            transition: opacity 0.2s, background-color 0.2s;
            text-decoration: none;
        }

        .btn:hover{
            opacity: 0.85;
        }

        .btn-danger{
            background-color: #fdf0ee;
            color: #aa2a1a;
            border-color: #e8b8b3;
        }

        .btn-danger:hover{
            background-color: #cc1100;
            color: #fff;
            border-color: #cc1100;
            opacity: 1;
        }

        /* NEBEZPEČNÁ SEKCE */
        .danger-sekce{
            background-color: #fdf0ee;
            border: 1px solid #e8b8b3;
            border-radius: 12px;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 4px;
        }

        .danger-sekce p{
            font-size: 0.88rem;
            color: #888;
            line-height: 1.5;
        }

        .danger-sekce input[type="password"]{
            padding: 10px 14px;
            background-color: #e2e0db;
            border: 1px solid #d0cdc7;
            border-radius: 10px;
            color: #1c1c1c;
            font-family: 'Roboto', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
            width: 100%;
            max-width: 320px;
        }

        .danger-sekce input[type="password"]:focus{
            border-color: #cc1100;
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
            .lang-box{
                min-width: unset;
                flex: 1;
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
            <p class="tagline">Profil</p>
        </div>
        <nav class="header-right">
            <a href="index.php">Hlavní stránka</a>
            <a href="slovicka.php">Slovník</a>
            <a href="prekladac.php">Překladač</a>
            <a href="zkouseni.php">Zkoušení</a>
            <a href="statistiky.php">Statistiky</a>
            <a href="balicek.php">Balíčky slov</a>
        </nav>
    </header>

    <!-- PANEL -->
    <div class="panel">

        <?php if($zprava != ""){?>
            <?php
                $zprava_trida = "zprava";
                if($zprava_typ == "chyba"){
                    $zprava_trida = "zprava chyba";
                }
            ?>
            <p class="<?php echo $zprava_trida; ?>">
                <?php echo htmlspecialchars($zprava); ?>
            </p>
        <?php } ?>

        <!-- INFORMACE O ÚČTU -->
        <h2>Informace o účtu</h2>
        <div class="info-row">
            <span class="label">Uživatelské jméno</span>
            <span class="value"><?php echo htmlspecialchars($user["username"]); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Celkem slovíček</span>
            <span class="value"><?php echo $celkem_slovicek; ?></span>
        </div>

        <!-- PŘEHLED SLOVNÍČKU -->
        <h2>Slovíčka podle jazyka</h2>
        <?php if(empty($slovicka_podle_jazyka)){ ?>
            <p style="color: #aaa;">Zatím nemáš žádná uložená slovíčka.</p>
        <?php }else{ ?>
            <div class="lang-boxy">
                <?php foreach($slovicka_podle_jazyka as $kod => $pocet){ ?>
                    <div class="lang-box">
                        <span class="lang-kod"><?php echo strtoupper($kod); ?></span>
                        <span class="lang-pocet"><?php echo $pocet; ?></span>
                        <span class="lang-label">
                            <?php
                                if($pocet == 1){
                                    echo 'slovíčko';
                                }elseif($pocet < 5){
                                    echo 'slovíčka';
                                }else{
                                    echo 'slovíček';
                                }
                            ?>
                        </span>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <!-- SMAZÁNÍ PODLE JAZYKA -->
        <h2>Smazat slovíčka podle jazyka</h2>
        <?php if(empty($slovicka_podle_jazyka)){ ?>
            <p style="color: #aaa;">Nemáš žádná slovíčka ke smazání.</p>
        <?php }else{ ?>
            <div class="danger-sekce">
                <p>Vyber jazyk jehož slovíčka chceš nenávratně smazat.</p>
                <?php foreach($slovicka_podle_jazyka as $kod => $pocet){ ?>
                    <form method="post" onsubmit="return confirm('Opravdu smazat všechna slovíčka v jazyce <?php echo strtoupper($kod); ?>?');">
                        <input type="hidden" name="lang_code" value="<?php echo htmlspecialchars($kod); ?>">
                        <button type="submit" name="smazat_jazyk" class="btn btn-danger">
                            Smazat <?php echo strtoupper($kod); ?> (<?php echo $pocet; ?> 
                            <?php if($pocet == 1){
                                echo 'slovíčko';
                            }elseif($pocet < 5){
                                echo 'slovíčka';
                            }else{
                                echo 'slovíček';
                            }?>)
                        </button>
                    </form>
                <?php } ?>
            </div>
        <?php } ?>

        <!-- SMAZÁNÍ VŠECH SLOVÍČEK -->
        <h2>Smazat všechna slovíčka</h2>
        <div class="danger-sekce">
            <?php if(empty($slovicka_podle_jazyka)){ ?>
                <p style="color: #aaa;">Nemáš žádná slovíčka ke smazání.</p>
            <?php }else{ ?>
                <p>Tato akce nenávratně smaže všechna tvá slovíčka ve všech jazycích včetně historie zkoušení.</p>
                <form method="post" onsubmit="return confirm('Opravdu chceš smazat všechna slovíčka? Tato akce je nevratná.');">
                    <button type="submit" name="smazat_vsechna" class="btn btn-danger">Smazat všechna slovíčka</button>
                </form>
            <?php } ?>
        </div>

        <!-- SMAZÁNÍ ÚČTU -->
        <h2>Smazat účet</h2>
        <div class="danger-sekce">
            <p>Tato akce nenávratně smaže tvůj účet, všechna slovíčka a historii zkoušení. Pro potvrzení zadej své heslo.</p>
            <form method="post" onsubmit="return confirm('Opravdu chceš smazat svůj účet? Tato akce je nevratná.');">
                <input type="password" name="heslo_potvrzeni" placeholder="Zadej heslo" required>
                <button type="submit" name="smazat_ucet" class="btn btn-danger">Smazat účet</button>
            </form>
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