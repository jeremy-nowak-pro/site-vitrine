import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import PieceCard from '@/components/catalogue/PieceCard';
import { useFavoris } from '@/hooks/useFavoris';
import PublicLayout from '@/layouts/PublicLayout';
import type { CartePiece } from '@/types/catalogue';

type Etat =
    | { statut: 'chargement' }
    | { statut: 'erreur' }
    | { statut: 'pret'; pieces: CartePiece[] };

export default function FavorisIndex() {
    const { references, retirer, vider } = useFavoris();
    const [etat, setEtat] = useState<Etat>({ statut: 'chargement' });
    const cle = references.join(',');

    useEffect(() => {
        if (cle === '') {
            setEtat({ statut: 'pret', pieces: [] });
            return;
        }
        const annulation = new AbortController();
        fetch(`/favoris/pieces?references=${encodeURIComponent(cle)}`, {
            headers: { Accept: 'application/json' },
            signal: annulation.signal,
        })
            .then((reponse) => (reponse.ok ? reponse.json() : Promise.reject(new Error(String(reponse.status)))))
            .then((donnees: { pieces: CartePiece[] }) => setEtat({ statut: 'pret', pieces: donnees.pieces }))
            .catch((erreur: unknown) => {
                if (!(erreur instanceof DOMException && erreur.name === 'AbortError')) {
                    setEtat({ statut: 'erreur' });
                }
            });

        return () => annulation.abort();
    }, [cle]);

    const pieces = etat.statut === 'pret' ? etat.pieces : [];
    const introuvables = etat.statut === 'pret' ? references.filter((r) => !pieces.some((p) => p.reference === r)) : [];

    return (
        <PublicLayout>
            <Head title="Favoris" />

            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Favoris</h1>
                    <p className="mt-1 text-sm text-ink-muted">
                        Enregistrés dans ce navigateur uniquement, sans compte.
                    </p>
                </div>
                {references.length > 0 && (
                    <button
                        type="button"
                        onClick={() => {
                            if (window.confirm('Retirer toutes les pièces de vos favoris ?')) vider();
                        }}
                        className="self-start text-sm text-accent hover:underline sm:self-auto"
                    >
                        Tout retirer
                    </button>
                )}
            </div>

            <div className="mt-6" aria-live="polite" aria-busy={etat.statut === 'chargement'}>
                {etat.statut === 'chargement' && <p className="text-sm text-ink-muted">Chargement des favoris…</p>}

                {etat.statut === 'erreur' && (
                    <p className="rounded border border-line bg-surface p-6 text-sm text-ink-muted">
                        Les favoris n’ont pas pu être chargés. Réessayez dans quelques instants.
                    </p>
                )}

                {etat.statut === 'pret' && references.length === 0 && (
                    <div className="rounded border border-line bg-surface p-6 text-sm">
                        <p className="font-medium">Aucun favori pour l’instant.</p>
                        <p className="mt-1 text-ink-muted">
                            Utilisez le bouton « Ajouter aux favoris » sur la fiche d’une pièce.
                        </p>
                        <Link href="/" className="mt-3 inline-block text-accent hover:underline">
                            Parcourir le catalogue
                        </Link>
                    </div>
                )}

                {pieces.length > 0 && (
                    <>
                        <p className="mb-3 text-sm text-ink-muted">
                            {pieces.length} pièce{pieces.length > 1 ? 's' : ''}
                        </p>
                        <ul className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                            {pieces.map((piece) => (
                                <li key={piece.id} className="flex flex-col gap-1.5">
                                    <PieceCard piece={piece} />
                                    <button
                                        type="button"
                                        onClick={() => retirer(piece.reference)}
                                        className="self-start px-1 text-xs text-ink-muted hover:text-ink hover:underline"
                                    >
                                        Retirer<span className="sr-only"> {piece.nom} des favoris</span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </>
                )}

                {introuvables.length > 0 && (
                    <p className="mt-6 text-sm text-ink-muted">
                        {introuvables.length} référence{introuvables.length > 1 ? 's' : ''} n’existe
                        {introuvables.length > 1 ? 'nt' : ''} plus dans le catalogue.{' '}
                        <button
                            type="button"
                            onClick={() => introuvables.forEach(retirer)}
                            className="text-accent hover:underline"
                        >
                            Les retirer
                        </button>
                    </p>
                )}
            </div>
        </PublicLayout>
    );
}
