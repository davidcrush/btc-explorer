import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { ChakraProvider, defaultSystem } from '@chakra-ui/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { UserPreferencesProvider } from './contexts/UserPreferencesContext';

const appName = import.meta.env.VITE_APP_NAME || 'btc-explorer';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx')
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <ChakraProvider value={defaultSystem}>
                <UserPreferencesProvider>
                    <App {...props} />
                </UserPreferencesProvider>
            </ChakraProvider>
        );
    },
    progress: {
        color: '#F59E0B',
    },
});
