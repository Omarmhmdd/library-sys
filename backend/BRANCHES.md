# Branch strategy

- **main** – stable; merge when a feature is done and tested.
- **feature/setup-backend-structure** – backend folders, base classes, conventions (current).
- **feature/books-models-migrations** – `books` table, `Book` model, seeders.
- **feature/auth-roles** – roles/permissions, SSO (Socialite), Sanctum API auth.
- **feature/books-crud** – Book CRUD API: controllers, requests, resources, services.
- **feature/checkout-checkin** – borrow/return, `borrowals` (or `checkouts`) table, rules.
- **feature/ai-setup** – OpenAI client, config, embedding + vector store (Pinecone/Chroma).
- **feature/ai-rag** – “Ask the Library” RAG endpoint + service.
- **feature/ai-metadata-suggestion** – suggest genre/description when adding/editing book.
- **feature/ai-similar-books** – similar books by embedding, endpoint + usage on book detail.

Merge order: setup → books-models → auth → books-crud → checkout → ai-setup → ai-rag → ai-metadata → ai-similar.
