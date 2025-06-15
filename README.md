# ANAF Facturare pentru WooCommerce

Plugin WordPress pentru integrarea automată a datelor companiei din sistemul ANAF în formularul de checkout WooCommerce.

## Descriere

Acest plugin oferă integrare automată cu sistemul ANAF pentru completarea automată a datelor companiei în formularul de checkout WooCommerce. Când un client introduce CUI-ul companiei, plugin-ul va căuta automat datele companiei în sistemul ANAF și va completa automat următoarele câmpuri:

- Numele companiei
- Numărul de înregistrare la Registrul Comerțului
- Adresa
- Oraș
- Județ
- Cod poștal

## Caracteristici

- Integrare cu API-ul ANAF pentru căutarea datelor companiei
- Completare automată a câmpurilor în checkout
- Suport pentru traduceri (română și engleză)
- Validare CUI
- Feedback vizual în timpul căutării
- Gestionarea erorilor și mesaje informative
- Compatibil cu cele mai recente versiuni de WordPress și WooCommerce

## Cerințe

- WordPress 5.0 sau mai nou
- WooCommerce 6.0 sau mai nou
- PHP 7.4 sau mai nou

## Instalare

1. Descărcați arhiva zip a plugin-ului
2. În panoul de administrare WordPress, mergeți la Plugins > Add New > Upload Plugin
3. Selectați arhiva descărcată și apăsați "Install Now"
4. După instalare, activați plugin-ul

## Utilizare

Plugin-ul funcționează automat în pagina de checkout WooCommerce. Când un client selectează opțiunea de facturare pentru companie și introduce un CUI valid, restul câmpurilor vor fi completate automat cu datele preluate de la ANAF.

## Dezvoltare

Acest plugin este dezvoltat și întreținut ca parte a unui sistem mai mare de aprovizionare WSL. Repository-ul principal poate fi găsit [aici](link-to-main-repo).

### Contribuții

Contribuțiile sunt binevenite! Vă rugăm să:

1. Fork acest repository
2. Creați un branch pentru feature-ul nou (`git checkout -b feature/AmazingFeature`)
3. Commit modificările (`git commit -m 'Add some AmazingFeature'`)
4. Push la branch (`git push origin feature/AmazingFeature`)
5. Deschideți un Pull Request

## Licență

Distribuit sub licența GPLv2 sau mai nouă. Vezi `LICENSE` pentru mai multe informații.

## Contact

Nume Proiect - [@twitter_handle](https://twitter.com/twitter_handle)

Link Proiect: [https://github.com/username/anaf-facturare](https://github.com/username/anaf-facturare)
