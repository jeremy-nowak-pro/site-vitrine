import { Form, Head, Link } from '@inertiajs/react';
import { BandeauDemo } from '@/layouts/AdminLayout';

export default function Connexion() {
    return (
        <div className="min-h-screen">
            <Head title="Connexion administration" />
            <BandeauDemo />

            <main className="mx-auto w-full max-w-sm px-4 pt-16 pb-10">
                <h1 className="text-xl font-semibold tracking-tight">Administration</h1>
                <p className="mt-1 text-sm text-ink-muted">Connectez-vous pour accéder au panneau.</p>

                <Form action="/admin/connexion" method="post" resetOnError={['password']} className="mt-8 space-y-4">
                    {({ errors, processing }) => (
                        <>
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium">
                                    Adresse e-mail
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="username"
                                    required
                                    autoFocus
                                    aria-invalid={errors.email ? true : undefined}
                                    aria-describedby={errors.email ? 'erreur-email' : undefined}
                                    className="mt-1.5 h-9 w-full rounded border border-line-strong px-3 text-sm"
                                />
                                {errors.email && (
                                    <p id="erreur-email" className="mt-1.5 text-sm text-red-700">
                                        {errors.email}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label htmlFor="password" className="block text-sm font-medium">
                                    Mot de passe
                                </label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    required
                                    className="mt-1.5 h-9 w-full rounded border border-line-strong px-3 text-sm"
                                />
                            </div>
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="remember" value="1" className="size-4 accent-accent" />
                                Rester connecté
                            </label>
                            <button
                                type="submit"
                                disabled={processing}
                                className="h-9 w-full rounded bg-accent text-sm font-medium text-white hover:bg-accent-strong disabled:opacity-60"
                            >
                                {processing ? 'Connexion…' : 'Se connecter'}
                            </button>
                        </>
                    )}
                </Form>

                <p className="mt-8 text-sm">
                    <Link href="/" className="text-ink-muted hover:text-ink">
                        ← Retour au catalogue
                    </Link>
                </p>
            </main>
        </div>
    );
}
