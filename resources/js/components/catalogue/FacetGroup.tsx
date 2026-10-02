import { useId, useState } from 'react';
import { formaterNombre } from '@/lib/catalogue';
import type { Facette } from '@/types/catalogue';

const VISIBLES = 8;

type Props = {
    facette: Facette;
    onBasculer: (valeur: string) => void;
};

export default function FacetGroup({ facette, onBasculer }: Props) {
    const [deplie, setDeplie] = useState(false);
    const idListe = useId();

    // Les valeurs cochées restent visibles même quand la liste est repliée.
    const options = deplie
        ? facette.options
        : facette.options.filter((option, index) => index < VISIBLES || option.actif);
    const masquees = facette.options.length - options.length;

    return (
        <fieldset className="border-t border-line py-4 first:border-t-0 first:pt-0">
            <legend className="float-left mb-2 w-full text-[13px] font-semibold">{facette.libelle}</legend>

            {facette.options.length === 0 ? (
                <p className="clear-left text-sm text-ink-faint">Aucune valeur</p>
            ) : (
                <ul id={idListe} className="clear-left space-y-0.5">
                    {options.map((option) => (
                        <li key={option.valeur}>
                            <label className="flex cursor-pointer items-center gap-2.5 rounded px-1 py-1 text-sm hover:bg-surface">
                                <input
                                    type="checkbox"
                                    checked={option.actif}
                                    onChange={() => onBasculer(option.valeur)}
                                    className="size-4 shrink-0 accent-accent"
                                />
                                <span className={`flex-1 ${option.actif ? 'font-medium' : ''}`}>{option.libelle}</span>
                                <span className="text-xs text-ink-faint tabular-nums">{formaterNombre(option.nombre)}</span>
                            </label>
                        </li>
                    ))}
                </ul>
            )}

            {(masquees > 0 || deplie) && (
                <button
                    type="button"
                    onClick={() => setDeplie(!deplie)}
                    aria-expanded={deplie}
                    aria-controls={idListe}
                    className="mt-1.5 px-1 text-sm text-accent hover:text-accent-strong hover:underline"
                >
                    {deplie ? 'Afficher moins' : `Afficher ${masquees} de plus`}
                </button>
            )}
        </fieldset>
    );
}
