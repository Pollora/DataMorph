# Datamorph - Package ETL pour Laravel

Datamorph est un package ETL (Extract, Transform, Load) puissant et flexible pour Laravel, permettant de gérer facilement les imports et exports de données.

## Installation

```bash
composer require pollora/datamorph
```

## Configuration

Publiez la configuration et les migrations :

```bash
php artisan vendor:publish --tag="datamorph-config"
php artisan vendor:publish --tag="datamorph-migrations"
php artisan migrate
```

## Fonctionnalités Principales

### Pipeline ETL de Base
```php
use Pollora\Datamorph\Extractors\CsvExtractor;
use Pollora\Datamorph\Transformers\DefaultTransformer;
use Pollora\Datamorph\Loaders\DatabaseLoader;

$pipeline = app('datamorph');

$pipeline
    ->from(new CsvExtractor(), ['path' => 'path/to/file.csv'])
    ->transform(new DefaultTransformer())
    ->to(new DatabaseLoader(), ['table' => 'users'])
    ->process();
```

### Traitement par Lots (Batch Processing)
```php
$pipeline
    ->from(new CsvExtractor(), [
        'path' => 'path/to/file.csv',
        'batch_size' => 1000 // Taille des lots
    ])
    ->transform(new DefaultTransformer())
    ->to(new DatabaseLoader(), ['table' => 'users'])
    ->process();
```

### Traitement Asynchrone
```php
$pipeline
    ->from(new CsvExtractor())
    ->transform(new DefaultTransformer())
    ->to(new DatabaseLoader(), ['table' => 'users'])
    ->async(ProcessDataJob::class) // Traitement en arrière-plan
    ->process();
```

### Logging et Monitoring
Le package inclut un système complet de logging et monitoring :

- Logs détaillés de chaque étape du pipeline
- Suivi des performances (temps d'exécution, nombre d'éléments traités)
- Stockage des logs en base de données
- Interface de consultation des logs (via les tables `datamorph_logs` et `datamorph_errors`)

### Gestion des Erreurs
Le système de gestion d'erreurs permet de :

- Capturer et enregistrer les erreurs survenues pendant le traitement
- Stocker les données qui ont échoué pour retraitement ultérieur
- Suivre les erreurs par batch et par étape du pipeline
- Reprendre le traitement à partir du dernier point de succès

## Configuration Avancée

Le fichier de configuration `config/datamorph.php` permet de personnaliser :

```php
return [
    // Configuration du logging
    'logging' => [
        'channel' => 'datamorph',
        'level' => 'info',
        'separate_errors' => true,
    ],

    // Configuration du batch processing
    'batch_size' => env('DATAMORPH_BATCH_SIZE', 500),
    'async' => [
        'enabled' => env('DATAMORPH_ASYNC_ENABLED', false),
        'queue' => env('DATAMORPH_QUEUE', 'default'),
        'connection' => env('DATAMORPH_QUEUE_CONNECTION', 'redis'),
    ],

    // ... autres configurations
];
```

## Tables de Base de Données

Le package crée deux tables principales :

### datamorph_logs
- Suivi des exécutions de pipeline
- Métriques de performance
- Statut des traitements

### datamorph_errors
- Détails des erreurs survenues
- Données en erreur pour retraitement
- Contexte de l'erreur (étape, batch, etc.)

## Bonnes Pratiques

1. **Gestion de la Mémoire**
   - Utilisez le traitement par lots pour les grands volumes de données
   - Configurez une taille de lot appropriée selon vos ressources

2. **Performance**
   - Activez le traitement asynchrone pour les imports volumineux
   - Utilisez les index de base de données appropriés

3. **Monitoring**
   - Consultez régulièrement les logs pour détecter les anomalies
   - Mettez en place des alertes sur les erreurs critiques

## Tests

```bash
composer test
```

## Contribution

Les contributions sont les bienvenues ! Consultez notre guide de contribution pour plus de détails.

## Licence

Ce package est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails. 