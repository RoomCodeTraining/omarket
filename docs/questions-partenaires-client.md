# Ôhéfê Market — Module Partenaires

Cadrage + implémentation (décisions cliente 21/07/2026).

---

## Décisions actées (implémentées)

| Point | Décision | Dans le code |
|--------|----------|--------------|
| Inscription | Autonome via le site | `/partenaires/inscription` |
| Validation compte | Non requise à l’inscription | Compte `partner` créé tout de suite |
| Publication / vente | Validation Ôhéfê obligatoire | `can_publish` + revue produit |
| Client vs partenaire | Mutuellement exclusifs | Enum `UserRole` |
| Fiches produits | Validées avant mise en ligne | `draft` → `pending_review` → `published` |
| Canaux | Stock, cargo, course | Inchangés |

## Commissions / paiements

**Pas encore décidé** — non implémenté.

---

## Flux actuel

1. Partenaire s’inscrit → rôle `partner`, `can_publish = false`
2. Il crée des **brouillons** dans `/partenaires/espace`
3. Admin autorise le partenaire dans Filament (**Partenaires** → Autoriser à publier)
4. Partenaire soumet le produit → `pending_review`
5. Admin publie dans Filament (**Produits** → Publier)
6. Le produit apparaît en boutique (`published` uniquement)

## Comptes seed

| E-mail | Rôle | Mot de passe |
|--------|------|--------------|
| `admin@ohefe.test` | Admin | `password` |
| `client@ohefe.test` | Client | `password` |
| `partenaire@ohefe.test` | Partenaire (pas encore autorisé) | `password` |

---

## Questions restantes

- Abonnement vs % commission
- Mode d’encaissement / reversement
- Panier multi-partenaires
- SAV / livraison centralisés ou non
