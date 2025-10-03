# Résolution de l'erreur 404 - API REST GSB

**Date :** 3 octobre 2025  
**Statut :** ✅ Résolu  
**Environnement :** nginx/1.26.3 (Ubuntu), PHP 8.4-FPM

---

## 📋 Symptômes

Lors de l'accès à l'API REST via l'URL `https://apigsb.unordinary.dev/medecin/45`, l'erreur suivante était retournée :

```
404 Not Found
nginx/1.26.3 (Ubuntu)
```

---

## 🔍 Diagnostic

### Logs nginx analysés

```bash
sudo tail -50 /var/log/nginx/error.log
```

**Erreurs identifiées :**

1. **Erreur 404 nginx** : Nginx ne trouvait pas le fichier physique correspondant à `/medecin/45`

2. **Warnings PHP** :
   ```
   PHP Warning: Undefined array key "ressource" in /var/www/restGSB/rest/restgsbrapports.php on line 51
   PHP Warning: Undefined array key "fonction" in /var/www/restGSB/rest/restgsbrapports.php on line 294
   ```

---

## 🐛 Problèmes identifiés

### Problème 1 : Configuration nginx incorrecte

**Fichier :** `/etc/nginx/sites-available/apigsb.conf`

**Problème :**
- La directive `try_files` redirigait vers `/index.php` au lieu de `wsGSB.php`
- Le paramètre `PATH_INFO` n'était pas passé au script PHP
- Nginx cherchait un fichier physique `/medecin/45` au lieu de traiter la requête comme une route REST

**Configuration initiale :**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### Problème 2 : Clé 'ressource' manquante dans la classe Rest

**Fichier :** `/var/www/restGSB/rest/rest.php`

**Problème :**
- La classe `Rest` ne récupérait jamais la ressource depuis l'URI
- Le tableau `$this->request` n'avait pas de clé `'ressource'`
- Le code dans `restgsbrapports.php` essayait d'accéder à `$this->request['ressource']` qui n'existait pas

**Code problématique dans restgsbrapports.php (ligne 51) :**
```php
$tab = explode('/', $this->request['ressource']); // ❌ Clé inexistante
```

### Problème 3 : Permissions Git incorrectes

**Problème :**
```bash
fatal: Unable to create '/var/www/restGSB/.git/index.lock': Permission denied
```

**Cause :** Le répertoire `/var/www/restGSB` appartenait à `root` alors que l'utilisateur courant était `unordinary`

---

## ✅ Solutions appliquées

### Solution 1 : Correction de la configuration nginx

**Action :** Modification du fichier `/etc/nginx/sites-available/apigsb.conf`

```bash
sudo nano /etc/nginx/sites-available/apigsb.conf
```

**Nouvelle configuration :**
```nginx
server {
    server_name apigsb.unordinary.dev;

    root /var/www/restGSB;
    index wsGSB.php;

    location / {
        # Redirige toutes les requêtes vers wsGSB.php
        try_files $uri $uri/ /wsGSB.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        # ✅ Ajout du paramètre PATH_INFO
        fastcgi_param PATH_INFO $fastcgi_path_info;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/apigsb.unordinary.dev/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/apigsb.unordinary.dev/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}

server {
    if ($host = apigsb.unordinary.dev) {
        return 301 https://$host$request_uri;
    }

    listen 80;
    server_name apigsb.unordinary.dev;
    return 404;
}
```

**Changements clés :**
- ✅ `try_files $uri $uri/ /wsGSB.php$is_args$args;` au lieu de `/index.php?$query_string`
- ✅ Ajout de `fastcgi_param PATH_INFO $fastcgi_path_info;`

**Application :**
```bash
# Tester la configuration
sudo nginx -t

# Recharger nginx
sudo systemctl reload nginx
```

### Solution 2 : Ajout de la récupération de la ressource dans Rest.php

**Fichier modifié :** `/var/www/restGSB/rest/rest.php`

**Code ajouté après le switch des méthodes HTTP (ligne ~77) :**
```php
/* Extrait la ressource depuis REQUEST_URI */
$uri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Enlève le nom du script de l'URI pour obtenir la ressource
$ressource = str_replace($scriptName, '', $uri);

// Enlève les paramètres de requête (après ?)
if (($pos = strpos($ressource, '?')) !== false) {
    $ressource = substr($ressource, 0, $pos);
}

// Nettoie les slashes au début et à la fin
$this->request['ressource'] = trim($ressource, '/');
```

**Explication :**
- Récupère l'URI complète depuis `$_SERVER['REQUEST_URI']`
- Retire le nom du script (`/wsGSB.php`) pour ne garder que la ressource
- Retire les paramètres de requête (query string)
- Stocke la ressource nettoyée dans `$this->request['ressource']`

**Exemple :**
- URL : `https://apigsb.unordinary.dev/medecin/45`
- `$_SERVER['REQUEST_URI']` = `/medecin/45`
- `$this->request['ressource']` = `medecin/45`

### Solution 3 : Correction des permissions Git

**Commande exécutée :**
```bash
sudo chown -R unordinary:unordinary /var/www/restGSB
```

**Vérification :**
```bash
ls -ld /var/www/restGSB
# drwxr-xr-x 4 unordinary unordinary 4096 Oct  3 09:46 /var/www/restGSB
```

---

## 🧪 Tests et validation

### Test 1 : Requête curl
```bash
curl https://apigsb.unordinary.dev/medecin/45
```

**Résultat attendu :** Code HTTP 200 avec données JSON

**Résultat obtenu :** ✅ Succès
```json
{
  "id": 45,
  "nom": "CORTES",
  "prenom": "Gilles",
  "adresse": "65 rue des oiseaux ARROUT 09800",
  "tel": "0578097401",
  ...
}
```

### Test 2 : Vérification des logs
```bash
sudo tail -f /var/log/nginx/error.log
```

**Résultat :** ✅ Aucune erreur PHP, requête traitée correctement

---

## 📊 Avant / Après

| Aspect | Avant | Après |
|--------|-------|-------|
| **Code HTTP** | 404 Not Found | 200 OK |
| **Traitement PHP** | ❌ Non exécuté | ✅ Exécuté |
| **Clé 'ressource'** | ❌ Undefined | ✅ Définie |
| **Routing REST** | ❌ Non fonctionnel | ✅ Fonctionnel |
| **Permissions Git** | ❌ Erreur | ✅ OK |

---

## 🎯 Conclusion

Les trois problèmes ont été résolus avec succès :

1. ✅ **Nginx** : Configuration corrigée pour router correctement les requêtes REST vers `wsGSB.php`
2. ✅ **PHP** : Ajout de la récupération de la ressource depuis l'URI dans la classe `Rest`
3. ✅ **Permissions** : Correction des propriétaires des fichiers pour permettre l'utilisation de Git

L'API REST fonctionne maintenant correctement et peut traiter les requêtes sur tous les endpoints définis.

---

## 📚 Références

- **Documentation nginx** : [FastCGI params](https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html)
- **PHP superglobals** : [`$_SERVER`](https://www.php.net/manual/fr/reserved.variables.server.php)
- **Architecture REST** : Bonnes pratiques de routing

---

**Auteur :** UnOrdinary  
**Validé par :** Tests fonctionnels sur environnement de production
