import { Head, router } from '@inertiajs/react';
import {
    Box,
    Button,
    Code,
    FieldRoot,
    FieldLabel,
    Flex,
    HStack,
    Input,
    Skeleton,
    Stack,
    Text,
} from '@chakra-ui/react';
import { useCallback, useEffect, useState } from 'react';
import axios from 'axios';
import AppLayout from '../../Layouts/AppLayout';
import { useUserPreferences } from '../../contexts/UserPreferencesContext';

const TXID_HEX = /^[a-fA-F0-9]{64}$/;

function RecentTxSkeleton() {
    return (
        <Box borderWidth="1px" borderColor="gray.700" rounded="lg" p={4} bg="gray.900">
            <Skeleton height="5" width="full" loading mb={2} />
            <Skeleton height="4" width="32" loading />
        </Box>
    );
}

export default function Index() {
    const [query, setQuery] = useState('');
    const [searchError, setSearchError] = useState(null);
    const [recent, setRecent] = useState([]);
    const [recentLoading, setRecentLoading] = useState(true);
    const [recentError, setRecentError] = useState(null);
    const { formatAmount } = useUserPreferences();

    const fetchRecent = useCallback(async () => {
        setRecentLoading(true);
        setRecentError(null);
        try {
            const response = await axios.get('/api/v1/btc/transactions/recent');
            setRecent(response?.data?.data?.transactions ?? []);
        } catch (err) {
            setRecentError(
                err?.response?.data?.message || 'Unable to load sample transactions right now.'
            );
            setRecent([]);
        } finally {
            setRecentLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchRecent();
    }, [fetchRecent]);

    const goToTransaction = (rawTxid) => {
        const trimmed = rawTxid.trim();
        if (TXID_HEX.test(trimmed)) {
            setSearchError(null);
            router.visit(`/transactions/${trimmed.toLowerCase()}`);
            return true;
        }
        setSearchError('Enter a valid 64-character hexadecimal transaction id.');
        return false;
    };

    const onSearchSubmit = (e) => {
        e.preventDefault();
        goToTransaction(query);
    };

    return (
        <>
            <Head title="Transactions" />

            <AppLayout
                title="Transactions"
                subtitle="Look up a transaction by id or browse a few from the latest block"
            >
                <Stack gap={8}>
                    <Box
                        as="form"
                        onSubmit={onSearchSubmit}
                        borderWidth="1px"
                        borderColor="gray.700"
                        rounded="lg"
                        p={4}
                        bg="gray.900"
                    >
                        <Stack gap={3}>
                            <FieldRoot>
                                <FieldLabel>Transaction id</FieldLabel>
                                <Input
                                    value={query}
                                    onChange={(e) => {
                                        setQuery(e.target.value);
                                        if (searchError) {
                                            setSearchError(null);
                                        }
                                    }}
                                    placeholder="64-character hex txid"
                                    fontFamily="mono"
                                    fontSize="sm"
                                    autoComplete="off"
                                    spellCheck={false}
                                />
                            </FieldRoot>
                            {searchError && (
                                <Text fontSize="sm" color="red.300">
                                    {searchError}
                                </Text>
                            )}
                            <Flex justify="flex-end">
                                <Button type="submit" colorPalette="orange">
                                    View transaction
                                </Button>
                            </Flex>
                        </Stack>
                    </Box>

                    <Stack gap={3}>
                        <Flex justify="space-between" align="center" wrap="wrap" gap={3}>
                            <Text fontWeight="medium">Latest confirmed (sample of 10)</Text>
                            <Button
                                size="sm"
                                variant="outline"
                                colorPalette="orange"
                                onClick={fetchRecent}
                                loading={recentLoading}
                            >
                                Refresh
                            </Button>
                        </Flex>

                        {recentLoading && (
                            <Stack gap={3}>
                                {Array.from({ length: 5 }, (_, i) => (
                                    <RecentTxSkeleton key={`tx-skel-${i}`} />
                                ))}
                            </Stack>
                        )}

                        {!recentLoading && recentError && (
                            <Box borderWidth="1px" borderColor="red.400" rounded="md" p={4}>
                                <Text color="red.200">{recentError}</Text>
                            </Box>
                        )}

                        {!recentLoading && !recentError && (
                            <Stack gap={3}>
                                {recent.map((tx) => (
                                    <Box
                                        key={tx.txid}
                                        borderWidth="1px"
                                        borderColor="gray.700"
                                        rounded="lg"
                                        p={4}
                                        bg="gray.900"
                                        cursor="pointer"
                                        transition="border-color 0.15s ease, background 0.15s ease"
                                        _hover={{ borderColor: 'orange.400', bg: 'gray.950' }}
                                        onClick={() => router.visit(`/transactions/${tx.txid}`)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' || e.key === ' ') {
                                                e.preventDefault();
                                                router.visit(`/transactions/${tx.txid}`);
                                            }
                                        }}
                                        role="button"
                                        tabIndex={0}
                                        aria-label={`View transaction ${tx.txid}`}
                                    >
                                        <Code
                                            whiteSpace="normal"
                                            wordBreak="break-all"
                                            colorPalette="gray"
                                            display="block"
                                            mb={2}
                                        >
                                            {tx.txid}
                                        </Code>
                                        <HStack>
                                            <Text fontSize="sm" color="gray.300">
                                                Fee: {formatAmount(tx.fee)}
                                            </Text>
                                        </HStack>
                                    </Box>
                                ))}
                                {recent.length === 0 && (
                                    <Text color="gray.400">No sample transactions available.</Text>
                                )}
                            </Stack>
                        )}
                    </Stack>
                </Stack>
            </AppLayout>
        </>
    );
}
