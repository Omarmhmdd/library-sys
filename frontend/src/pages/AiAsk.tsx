import { useState } from 'react';
import { useAiAsk } from '../queries';

export function AiAsk() {
  const [question, setQuestion] = useState('');
  const { answer, loading, error, ask } = useAiAsk();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (question.trim()) ask(question.trim());
  };

  return (
    <div className="max-w-2xl mx-auto px-4">
      <h1 className="font-display font-semibold text-2xl mb-2">Ask the Library</h1>
      <p className="text-zinc-500 mb-2">
        Ask a question about the book catalog. Answers are based on the books in the library.
      </p>
      <p className="text-zinc-500 text-sm mb-6">
        Other AI features: <strong>Similar books</strong> on each book’s page; <strong>Suggest genre & description</strong> on Add book / Edit book (fill title & author, then click the AI button).
      </p>
      <form onSubmit={handleSubmit} className="card mb-4">
        <div className="mb-4">
          <label className="block text-zinc-500 text-sm mb-1">Question</label>
          <textarea
            value={question}
            onChange={(e) => setQuestion(e.target.value)}
            rows={3}
            placeholder="e.g. What programming books do you have?"
            required
            className="input"
          />
        </div>
        <button type="submit" disabled={loading} className="btn">
          {loading ? 'Asking…' : 'Ask'}
        </button>
      </form>
      {error && <p className="text-rose-400 text-sm mb-4">{error}</p>}
      {answer && (
        <div className="card border-cyan-400/50 bg-zinc-900/50">
          <h3 className="font-display font-semibold text-lg mt-0 mb-2">Answer</h3>
          <p className="whitespace-pre-wrap m-0">{answer}</p>
        </div>
      )}
    </div>
  );
}
