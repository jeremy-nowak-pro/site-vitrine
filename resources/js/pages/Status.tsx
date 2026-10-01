import { Head } from '@inertiajs/react';
import PublicLayout from '@/layouts/PublicLayout';

type Service = { name: string; ok: boolean; detail: string };

const swatches = [
    ['canvas', 'Fond'],
    ['surface', 'Surface'],
    ['surface-strong', 'Surface appuyée'],
    ['line', 'Bordure'],
    ['ink-muted', 'Texte secondaire'],
    ['ink', 'Texte'],
    ['accent', 'Accent'],
] as const;

export default function Status({ services }: { services: Service[] }) {
    return (
        <PublicLayout>
            <Head title="Socle technique" />

            <p className="text-xs font-medium uppercase tracking-wide text-ink-faint">Étape 1</p>
            <h1 className="mt-1 text-2xl font-semibold tracking-tight">Socle technique</h1>
            <p className="mt-2 max-w-2xl text-ink-muted">
                Page provisoire. Elle vérifie que les services Sail répondent et montre les tokens de design. Le catalogue
                prendra sa place à l’étape 4.
            </p>

            <section aria-labelledby="services" className="mt-10">
                <h2 id="services" className="text-sm font-semibold">
                    Services
                </h2>
                <table className="mt-3 w-full max-w-2xl border-collapse text-sm">
                    <tbody>
                        {services.map((service) => (
                            <tr key={service.name} className="border-t border-line last:border-b">
                                <th scope="row" className="py-2.5 pr-4 text-left font-medium">
                                    {service.name}
                                </th>
                                <td className="py-2.5 pr-4 text-ink-muted">
                                    <span className="block max-w-md truncate">{service.detail}</span>
                                </td>
                                <td className="py-2.5 text-right">
                                    <span className={service.ok ? 'text-accent' : 'text-red-700'}>
                                        {service.ok ? 'OK' : 'Erreur'}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>

            <section aria-labelledby="tokens" className="mt-12">
                <h2 id="tokens" className="text-sm font-semibold">
                    Couleurs
                </h2>
                <ul className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                    {swatches.map(([token, label]) => (
                        <li key={token} className="text-xs">
                            <span
                                className="block h-12 rounded border border-line"
                                style={{ background: `var(--color-${token})` }}
                            />
                            <span className="mt-1.5 block font-medium">{label}</span>
                            <code className="font-mono text-ink-faint">{token}</code>
                        </li>
                    ))}
                </ul>
            </section>

            <section aria-labelledby="composants" className="mt-12 max-w-2xl">
                <h2 id="composants" className="text-sm font-semibold">
                    Typographie et contrôles
                </h2>
                <div className="mt-3 space-y-1">
                    <p className="text-2xl font-semibold tracking-tight">Jante 5 branches Rallye 1/18</p>
                    <p className="text-base">Texte courant en 16 px, gris anthracite.</p>
                    <p className="text-sm text-ink-muted">Texte secondaire, références et métadonnées.</p>
                </div>
                <div className="mt-5 flex flex-wrap items-center gap-3">
                    <button className="rounded bg-accent px-3.5 py-2 text-sm font-medium text-white hover:bg-accent-strong">
                        Action principale
                    </button>
                    <button className="rounded border border-line-strong px-3.5 py-2 text-sm font-medium hover:bg-surface">
                        Action secondaire
                    </button>
                    <button disabled className="rounded bg-surface-strong px-3.5 py-2 text-sm font-medium text-ink-faint">
                        Désactivé
                    </button>
                    <label className="flex items-center gap-2 text-sm">
                        <span className="sr-only">Recherche</span>
                        <input
                            type="search"
                            placeholder="Rechercher une pièce"
                            className="w-56 rounded border border-line-strong px-3 py-2 placeholder:text-ink-faint"
                        />
                    </label>
                </div>
            </section>
        </PublicLayout>
    );
}
