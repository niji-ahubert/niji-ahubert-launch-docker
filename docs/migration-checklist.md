# Checklist de migration vers le système unifié d'événements

## ✅ Composants créés

- [x] `MessageDisplayAdapter.php`
- [x] Documentation architecture
- [x] `unified-event-system.md`

## ✅ Services refactorisés

- [x] `AbstractProjectStrategy.php`
- [x] `CreateApplicationService.php`
- [x] `ProjectGenerationService.php`
- [x] `MakeEnvProject.php`

## 🔄 Step Handlers à migrer

### Priorité haute (utilisés fréquemment)

- [x] `ComposerInitStepHandler.php` ✅ FAIT
- [x] `SymfonyCreateStepHandler.php`
- [x] `LaravelCreateStepHandler.php`
- [x] `NodeInitStepHandler.php`
- [x] `FolderProjectCreateStepHandler.php`

### Priorité moyenne

- [x] `DockerFileStepHandler.php`
- [x] `EnvFileCreateStepHandler.php`
- [x] `StartPagePhpStepHandler.php`

### À identifier

- [ ] Autres handlers dans `src/Strategy/Step/`

## 🔄 Docker Services à vérifier

### Dans `src/Strategy/DockerService/`

- [ ] `AbstractDockerService.php`
- [ ] Tous les services Docker implémentant `AbstractDockerService`

## 🧪 Tests à effectuer

### Tests unitaires

- [ ] `MessageDisplayAdapterTest.php`
- [ ] Test de chaque handler migré
- [ ] Test de `CreateApplicationService::executeWithEvents()`

### Tests d'intégration

- [ ] Génération complète de projet en CLI
- [ ] Streaming SSE via web
- [ ] Vérifier que tous les messages s'affichent correctement

## 📝 Documentation

- [x] Architecture unifiée documentée
- [ ] Exemples d'utilisation dans le README
- [ ] Guide de contribution mis à jour

## 🎯 Objectifs

### Court terme

1. Migrer les 5 handlers priorité haute
2. Tester la génération de projet complète en CLI
3. Vérifier qu'il n'y a plus de duplication de messages

### Moyen terme

1. Migrer tous les handlers restants
2. Ajouter tests unitaires complets
3. Créer route web pour streaming SSE de la génération

### Long terme

1. Étendre à d'autres contextes (logs fichiers, notifications, etc.)
2. Ajouter métriques et monitoring
3. Système de replay d'événements pour debugging

## 📊 Progression

- **Handlers migrés** : 1/15+ (7%)
- **Services refactorisés** : 4/4 (100%)
- **Documentation** : 90%
- **Tests** : 0%

## 🚀 Commande pour tester

```bash
# Test CLI
php bin/console make:project:new --client=test-client --project=test-project

# Vérifier qu'il n'y a plus de messages dupliqués
# Vérifier que l'affichage est cohérent
```

## ⚠️ Points d'attention

1. **Gestion de SymfonyStyle null** : Les handlers doivent fonctionner même si $io est null (mode web)
2. **Compatibilité backward** : L'adaptateur supporte temporairement les yield string legacy
3. **Exceptions** : Continuer à throw les exceptions pour les erreurs critiques
4. **Performance** : Éviter de consommer les générateurs plusieurs fois

## 📞 Support

Pour toute question sur la migration :

- Consulter `docs/architecture/unified-event-system.md`
- Voir l'exemple dans `ComposerInitStepHandler.php`
- Vérifier les tests existants
