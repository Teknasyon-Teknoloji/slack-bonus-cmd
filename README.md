KURULUM
==

Webserver
--
* Proje dosyaları sunucuda seçilen dizine kopyalanır.
* Proje ana dizinindeki "log" dizinine "chmod 777 log" komutu ile genel yazma hakkı verilir.
* Proje ana dizinindeki iken "composer update --no-dev" komutu ile composer paketleri kurulur.
* Bir domain/subdomain belirlenerek webserver ayarları "public/" dizine bakacak şekilde yapılır. Slack buraya istek göndereceğinden sadece "public/" dizini dışarı
açılmalıdır.

DB Kurulum
--
"bonus.sql" SQL dosyası MySQL sunucu üzerinde çalıştırılır.
"config.inc.php" dosyasında DB ayarları yapılır.

SMTP
--
Mail gönderimleri için bir smtp sunucusunda mail hesabbı ayarlanır ve ilgili SMTP ayarları "config.inc.php" dosyasında yapılır.
Mail alacak adminlerin  mail adresleri "config.inc.php" dosyasında $ADMIN_MAILS arrayına eklenir.

SLACK
--
* Slack yönetim ekranından (https://<MYCOMPANY>.slack.com/apps/manage/custom-integrations) "Slash Commands" sayfasına gidilir.
* Add Configuration ile yeni slack komutu (/bonus) tanımlanır. 
* Tanımlama yapılırken POST url olarak http://<MYDOMAIN>/bonus.php urli verilir. ( Werserver ayarlarında hangi domain ayarlanmışsa o kullanılır.)
* Tanımlama sonucunda Slack otomatik oluşturduğu token bilgisi "config.inc.php" dosyasına yazılır.
* Komut için isim, ikon, açıklama yazılır.
* "Escape channels, users, and links" ayarı "On" durumuna getirilir.
* "Translate User IDs" ayarı seçili duruma getirilir.
* Bu komut ile rapor alacak kullanıcı isimleri "config.inc.php" dosyasında $SLACK_ADMIN_USERNAMES arrayinde ayarlanmalıdır.
* Ayrıca slack user bilgilerini almak için Slack API istekleri atabilmek için Slack api token ayarlanır.
Bunun için webde slack admin tarafından https://api.slack.com/custom-integrations/legacy-tokens
adresinden api token oluşturulur ve "config.inc.php" dosyasındaki "SLACK_API_TOKEN" ayarına eklenir.

CRON
--
* Sunucu üzerinde her dakika çalışacak şekilde "process_bonus.php" dosyası cronda ayarlanır. Örnek ayar:
```
* * * * * php /PROJECT/PATH/process_bonus.php
```

"/bonus" Komutu
--
* Kullanıcılar slack ekranlarında /bonus @kullanıcı "açıklama" ile başka bir kullanıcıya bonus verebilirler.
* Bonus default 50TLdir. Bu db config tablosundan güncellenebilir.
* Bir kullanıcı ayda 2 adet bonus verilebilir. Bu db config tablosundan güncellenebilir.
* Bonus kaydı işlendiğinde kullanıcıya slackten bilgi gider, ayarlanış admin maillerine de mail gider.
* Kullanıcı hiçbir parametre vermeden sadece /bonus komutu ile kendi bonus durumuna bakar.
* Slack admin userları /bonus rapor komutu ile kendi mail adreslerine rapor gönderebilirler.
