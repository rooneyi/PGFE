# Guide d’installation PGFE — Windows (client)

Installer et lancer **PGFE** sur un PC Windows **sans taper de commandes au quotidien**.

**Code :** https://github.com/rooneyi/PGFE

---

## 1. Une seule fois — installateur automatique

Vous n’avez **pas** besoin d’installer Laragon, Node.js ou Git à la main.

### Option A — Fichier installateur (recommandé)

1. Téléchargez `INSTALLER-PGFE.bat` (ou le dépôt ZIP / clone GitHub)
2. Double-cliquez sur **`INSTALLER-PGFE.bat`**

L’installateur :
- installe **Git**, **Node.js 20+** et **Laragon** s’ils manquent (fenêtre UAC possible)
- **clone** le projet dans `%USERPROFILE%\Desktop\PGFE` s’il n’est pas déjà présent  
  (repli : `%USERPROFILE%\PGFE` ; si vous lancez depuis un clone existant, aucun nouveau clone)
- lance `scripts\setup-pgfe.bat` (composer, npm, base, migrations, seeders)
- crée le raccourci **`PGFE.lnk` sur le Bureau**

### Option B — Déjà cloné

```bat
git clone https://github.com/rooneyi/PGFE.git
cd PGFE
scripts\setup-pgfe.bat
```

`setup-pgfe.bat` installe aussi les outils manquants, puis prépare backend + frontend.

Après Laragon : menu **PHP** → choisissez **PHP 8.4** si besoin.

> Si l’URL de téléchargement Laragon change, voir https://laragon.org/download/ ou https://github.com/leokhoa/laragon/releases

---

## 2. Usage quotidien

Double-cliquez sur **`PGFE.lnk`** sur le **Bureau**.

Cela démarre :
1. Laragon (fenêtre normale)
2. le backend Laravel et le frontend Vue **en arrière-plan** (pas de fenêtres CMD noires)
3. le navigateur sur **http://localhost:5173**

Logs de debug : `scripts\logs\backend.log` et `scripts\logs\frontend.log`

Pour arrêter backend + frontend : `scripts\stop-pgfe.bat`  
(Laragon reste ouvert.)

Si le raccourci manque :

```
scripts\create-shortcut.bat
```

Le script affiche le **chemin complet** du raccourci Bureau (y compris Bureau OneDrive).

---

## 3. Connexion

| Email | Mot de passe |
|--------|--------------|
| `superadmin@pgfe.com` | `SuperAdmin@2025` |

Changez le mot de passe après la 1ʳᵉ connexion.

---

## En cas de problème

| Problème | Solution |
|----------|----------|
| Rien ne démarre | Ouvrez Laragon → **Start All**, puis relancez `PGFE.lnk` |
| Outils manquants | Relancez `INSTALLER-PGFE.bat` ou `scripts\setup-pgfe.bat` |
| Erreur « PHP version >= 8.4 » | Laragon → PHP → 8.4 |
| Erreur MySQL / migrate | Laragon **Start All** ; mot de passe MySQL souvent vide |
| Page blanche | Attendez 5–10 s ; vérifiez `scripts\logs\` |
| Raccourci invisible | Relancez `scripts\create-shortcut.bat` ; regardez aussi OneDrive\Desktop |
| Port déjà utilisé | Normal si déjà lancé ; ou `stop-pgfe.bat` puis relancez |

---

*Installation client Windows — https://github.com/rooneyi/PGFE*
