import { formaterMm } from '@/lib/catalogue';
import type { FichePiece } from '@/types/piece';

/**
 * Emplacement de la visionneuse 3D. Remplacé à l'étape 6 par le composant
 * chargé à la demande (React Three Fiber).
 */
export default function ZoneVisionneuse({ piece }: { piece: FichePiece }) {
    return (
        <div
            className="flex aspect-[4/3] flex-col items-center justify-center gap-2 rounded-md border border-line bg-surface text-center"
            style={{
                backgroundImage:
                    'linear-gradient(var(--color-line) 1px, transparent 1px), linear-gradient(90deg, var(--color-line) 1px, transparent 1px)',
                backgroundSize: '24px 24px',
            }}
        >
            <p className="rounded bg-canvas px-3 py-1.5 text-sm text-ink-muted">Visionneuse 3D : étape 6</p>
            <p className="rounded bg-canvas px-2 py-1 font-mono text-xs text-ink-faint">
                {formaterMm(piece.dimensions.longueur)} × {formaterMm(piece.dimensions.largeur)} × {formaterMm(piece.dimensions.hauteur)}
            </p>
        </div>
    );
}
