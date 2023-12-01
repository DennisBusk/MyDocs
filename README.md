<div class="flex justify-center w-full" style="width:100%; display: flex; justify-content: center; margin-top: 10px;">
<a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a>
<a href="https://filamentphp.com/" target="_blank"><img src="https://cms.atyantik.com/uploads/Microsoft_Teams_image_20_284afdc0b6.png" width="400" alt="Filamentphp Logo"></a>
</div>
<h1 align="center">MyDocs</h1>
<p style="width: 100%; text-align:center;">
Laravel 10 / FilamentPHP 3.0 Projekt
</p>

Installation:
1. Klon projektet til din lokale maskine:<br>
   git clone https://github.com/DennisBusk/MyDocs
<br><br>
2. Skift til projektmappen:<br>
   cd MyDocs<br><br>

3. Dupliker .env.example fil og opdater den med dine databaseoplysninger:<br>
   cp .env.example .env<br><br>
   Åbn .env filen og opdater følgende linjer:
   <br>
   <br>
4. Installer afhængigheder:<br>
   composer install<br>
npm install<br>
npm run build<br><br>

DB_CONNECTION=mysql<br>
   DB_HOST=127.0.0.1<br>
   DB_PORT=3306<br>
   DB_DATABASE=dit_database_navn<br>
   DB_USERNAME=dit_database_brugernavn<br>
   DB_PASSWORD=dit_database_password<br>
<br>

5. Generer en applikationsnøgle:<br>
   php artisan key:generate
<br><br>
6. Storage link:<br>
   php artisan storage:link
<br><br>
7. Kør projektet:<br>
Kør Laravel-udviklingsserveren:<br>
php artisan serve<br><br>
Åbn din browser og besøg http://localhost:8000 for at se dit projekt.

<br><br>
8. Eller:<br>
Åbn din browser og besøg https://mydocs.dennisbusk.dk.

Løsningen:
Efter oprettelse af en bruger og log ind, kan du:
- Uploade dokumenter/billeder.
- Dele dokumenter/billeder med andre brugere.
- Deler dokumenter/billeder via email ( Under udvikling )
- Se og downloade dine og delte dokumenter/billeder.
- Bulk dele og downloade dokumenter/billeder

<br><br>
Alt i alt har dette taget lidt over 6 timer.
Google cloud store drillede mig en lille smule mht download.

Venlig hilsen<br>
Dennis Busk

