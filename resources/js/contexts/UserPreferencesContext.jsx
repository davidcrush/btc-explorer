import { createContext, useContext, useEffect, useMemo, useState } from 'react';

const UserPreferencesContext = createContext(null);

export function UserPreferencesProvider({ children }) {
    const [amountUnit, setAmountUnit] = useState('bitcoin');

    useEffect(() => {
        const storedUnit = window.localStorage.getItem('amount-unit');

        if (
            storedUnit === 'bitcoin' ||
            storedUnit === 'millibit' ||
            storedUnit === 'bit' ||
            storedUnit === 'satoshi'
        ) {
            setAmountUnit(storedUnit);
        }
    }, []);

    useEffect(() => {
        window.localStorage.setItem('amount-unit', amountUnit);
    }, [amountUnit]);

    const formatAmount = useMemo(
        () => (value) => {
            const sats = Number(value || 0);

            if (amountUnit === 'bitcoin') {
                return `${new Intl.NumberFormat(undefined, {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 8,
                }).format(sats / 100000000)} btc`;
            }

            if (amountUnit === 'millibit') {
                return `${new Intl.NumberFormat(undefined, {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 5,
                }).format(sats / 100000)} mBTC`;
            }

            if (amountUnit === 'bit') {
                return `${new Intl.NumberFormat(undefined, {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                }).format(sats / 100)} μBTC`;
            }

            return `${new Intl.NumberFormat().format(sats)} sat`;
        },
        [amountUnit]
    );

    const value = useMemo(
        () => ({
            amountUnit,
            setAmountUnit,
            formatAmount,
        }),
        [amountUnit, formatAmount]
    );

    return (
        <UserPreferencesContext.Provider value={value}>
            {children}
        </UserPreferencesContext.Provider>
    );
}

export function useUserPreferences() {
    const context = useContext(UserPreferencesContext);

    if (context === null) {
        throw new Error('useUserPreferences must be used within UserPreferencesProvider.');
    }

    return context;
}
