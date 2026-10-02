import { Link } from '@inertiajs/react';
import type { CartePiece } from '@/types/catalogue';

export default function PieceCard({ piece }: { piece: CartePiece }) {
    return (
        <Link
            href={`/pieces/${piece.reference}`}
            className="group flex h-full flex-col overflow-hidden rounded-md border border-line bg-canvas hover:border-line-strong"
        >
            <div className="aspect-[4/3] bg-surface">
                {piece.miniature && (
                    <img
                        src={piece.miniature}
                        alt=""
                        width={480}
                        height={360}
                        loading="lazy"
                        decoding="async"
                        className="size-full object-cover"
                    />
                )}
            </div>
            <div className="flex flex-1 flex-col gap-1 border-t border-line p-3">
                <span className="font-mono text-[11px] text-ink-faint">{piece.reference}</span>
                <span className="text-sm font-medium leading-snug group-hover:text-accent">{piece.nom}</span>
                <span className="mt-auto pt-1 text-xs text-ink-muted">
                    {piece.categorie_nom} · {piece.echelle_libelle} · {piece.fabricant_nom}
                </span>
            </div>
        </Link>
    );
}
