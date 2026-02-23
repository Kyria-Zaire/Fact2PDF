# Méthode BMAD - Breakthrough Method for Agile AI-Driven Development

## Introduction
BMAD est un framework agile pour le développement assisté par IA (comme Cursor, Claude). Il orchestre l'IA comme une équipe d'experts (agents) pour produire du code scalable, du prototype à la prod. Adapté à tout projet, de bugfix à app enterprise.

- **Pourquoi BMAD ?** : Structure l'IA pour éviter chaos (code non scalable). Augmente productivité x10 en guidant l'IA via prompts persona-based.
- **Composants Clés** :
  - **Agents IA** : Rôles spécialisés (Analyst, Product Manager, Architect, Scrum Master, Developer, QA).
  - **Workflows** : YAML-based pour tâches séquentielles.
  - **Phases** : 4 phases structurées.
  - **Outils** : Intégrable dans IDE (Cursor), avec configs pour prompts persistants.

## Phases de la Méthode BMAD
BMAD divise le dev en 2 macro-phases (Planning Agentic + Implémentation), mais détaillé en 4 étapes itératives.

### Phase 1 : Analyse (Agent : Analyst)
- **Objectif** : Comprendre le besoin, recueillir requirements.
- **Étapes** :
  1. Project Brief : Décrire le problème (ex. : "Générer PDF factures").
  2. Requirements Gathering : Lister features, contraintes (use PRD).
  3. Risk Assessment : Identifier pitfalls (ex. : Sécurité PHP sans framework).
- **Prompt IA Exemple** : "En tant qu'Analyst senior, analyse ce brief [insérer] et produis une liste de requirements détaillées."
- **Output** : Doc requirements (markdown).

### Phase 2 : Planning (Agents : PM, Product Owner, Scrum Master)
- **Objectif** : Planifier tâches, prioriser.
- **Étapes** :
  1. User Stories : Écrire en format "As a [user], I want [feature] so that [benefit]".
  2. Task Breakdown : Décomposer en sprints/tâches (ex. : Setup DB, Impl CRUD).
  3. Estimation : Temps/effort, avec scaling adaptatif (simple pour MVP, deep pour complex).
- **Prompt IA Exemple** : "En tant que PM expérimenté, à partir de ces requirements [insérer], crée un backlog de user stories et un plan sprint."
- **Output** : Backlog, roadmap (Gantt ou liste).

### Phase 3 : Architecture (Agent : Architect)
- **Objectif** : Designer la structure technique.
- **Étapes** :
  1. High-Level Design : Schema DB, API endpoints, stack.
  2. Tech Choices : Justifier (ex. : Docker pour isolation).
  3. Scalability Plan : Comment scaler (caching, DB sharding).
- **Prompt IA Exemple** : "En tant qu'Architect tech lead, design l'architecture pour [brief], incluant schema DB et config Docker."
- **Output** : Diagrams (text-based UML), specs tech.

### Phase 4 : Implémentation & QA (Agents : Developer, QA)
- **Objectif** : Coder, tester, itérer.
- **Étapes** :
  1. Code Generation : IA génère code par tâche.
  2. Review & Refine : Humain vérifie, ajuste.
  3. Testing : Unit/integration, fix bugs.
  4. Deployment : Push to prod (Vercel).
- **Prompt IA Exemple** : "En tant que Developer senior, implémente cette tâche [détail] en pure PHP, scalable et commenté."
- **Output** : Code, tests, logs.

## Meilleures Pratiques BMAD
- **Persona Prompts** : Toujours spécifier rôle IA (ex. : "Tech Lead DevOps") pour qualité.
- **Itérations** : Boucle courte (daily standups virtuels : review outputs IA).
- **Scale-Adaptive** : Pour petits tasks, light planning ; pour core, deep.
- **Intégration IDE** : Dans Cursor, configure prompts persistants via configs YAML.
- **Mesure** : Track temps (IA vs humain), qualité (bugs found).
- **Extensions** : Ajoute agents custom (ex. : DevOps pour Docker).

## Application à Notre Projet
- On applique BMAD par milestone (ex. : Phase 1 pour PRD).
- Rôles : Moi (CTO) = Scrum Master/Architect ; Toi = Developer/PM ; IA = Analyst/QA.

---
Références : GitHub BMAD-METHOD, docs.bmad-method.org. Version : 1.0 | Date : 23/02/2026.
