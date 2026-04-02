import { Head, router } from '@inertiajs/react';
import {
    Badge,
    Box,
    Button,
    Code,
    Flex,
    HStack,
    Spinner,
    Stack,
    Text,
} from '@chakra-ui/react';
import { useCallback, useEffect, useState } from 'react';
import axios from 'axios';
import AppLayout from '../../Layouts/AppLayout';
import { useUserPreferences } from '../../contexts/UserPreferencesContext';

const PAGE_SIZE = 25;

export default function Index() {
    const [stats, setStats] = useState(null);
    const [transactions, setTransactions] = useState([]);
    const [totalCount, setTotalCount] = useState(0);
    const [hasMore, setHasMore] = useState(false);
    const [loading, setLoading] = useState(true);
    const [loadingMore, setLoadingMore] = useState(false);
    const [error, setError] = useState(null);
    const { formatAmount } = useUserPreferences();

    const reload = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const [statsRes, txRes] = await Promise.all([
                axios.get('/api/v1/btc/mempool/stats'),
                axios.get('/api/v1/btc/mempool/transactions', {
                    params: { offset: 0, limit: PAGE_SIZE },
                }),
            ]);
            setStats(statsRes?.data?.data?.stats ?? null);
            const data = txRes?.data?.data;
            setTransactions(data?.transactions ?? []);
            setTotalCount(data?.total_count ?? 0);
            setHasMore(Boolean(data?.has_more));
        } catch (err) {
            setError(err?.response?.data?.message || 'Unable to load mempool right now.');
            setStats(null);
            setTransactions([]);
            setTotalCount(0);
            setHasMore(false);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        reload();
    }, [reload]);

    const loadMore = useCallback(async () => {
        if (!hasMore || loadingMore) {
            return;
        }
        setLoadingMore(true);
        try {
            const response = await axios.get('/api/v1/btc/mempool/transactions', {
                params: { offset: transactions.length, limit: PAGE_SIZE },
            });
            const data = response?.data?.data;
            const next = data?.transactions ?? [];
            setTransactions((prev) => [...prev, ...next]);
            setHasMore(Boolean(data?.has_more));
        } catch {
            // Keep existing list; user can refresh.
        } finally {
            setLoadingMore(false);
        }
    }, [hasMore, loadingMore, transactions.length]);

    return (
        <>
            <Head title="Mempool" />

            <AppLayout
                title="Mempool"
                subtitle="Unconfirmed txids from GET /mempool/txids — open a row for full details"
            >
                <Stack gap={6}>
                    <Flex justify="flex-end">
                        <Button colorPalette="orange" variant="solid" onClick={() => reload()}>
                            Refresh
                        </Button>
                    </Flex>

                    {loading && (
                        <HStack>
                            <Spinner size="sm" color="orange.300" />
                            <Text color="gray.300">Loading mempool…</Text>
                        </HStack>
                    )}

                    {!loading && error && (
                        <Box borderWidth="1px" borderColor="red.400" rounded="md" p={4}>
                            <Text color="red.200">{error}</Text>
                        </Box>
                    )}

                    {!loading && stats && (
                        <Box borderWidth="1px" borderColor="gray.700" rounded="lg" p={4} bg="gray.900">
                            <Text fontWeight="medium" mb={3}>
                                Backlog (GET /mempool)
                            </Text>
                            <HStack wrap="wrap" gap={3} mb={4}>
                                <Badge colorPalette="orange">Count: {stats.count}</Badge>
                                <Badge colorPalette="blue">vsize: {stats.vsize.toLocaleString()} vB</Badge>
                                <Badge colorPalette="purple">
                                    Total fees: {formatAmount(stats.total_fee)}
                                </Badge>
                            </HStack>
                            <Text fontSize="sm" color="gray.300" mb={2}>
                                Fee histogram (sat/vB vs vB in band)
                            </Text>
                            <Stack gap={1}>
                                {(stats.fee_histogram ?? []).slice(0, 12).map((row, index) => (
                                    <Text key={`hist-${index}`} fontSize="xs" color="gray.400">
                                        {index === 0 ? '≥ ' : '> '}
                                        {typeof row[0] === 'number' ? row[0].toFixed(2) : row[0]} sat/vB —{' '}
                                        {row[1]?.toLocaleString?.() ?? row[1]} vB
                                    </Text>
                                ))}
                                {(stats.fee_histogram ?? []).length === 0 && (
                                    <Text fontSize="xs" color="gray.500">
                                        No histogram data.
                                    </Text>
                                )}
                            </Stack>
                        </Box>
                    )}

                    {!loading && !error && (
                        <Box borderWidth="1px" borderColor="gray.700" rounded="lg" p={4} bg="gray.900">
                            <Text fontWeight="medium" mb={3}>
                                Transaction ids ({transactions.length} shown
                                {totalCount > 0 ? ` of ${totalCount.toLocaleString()}` : ''})
                            </Text>
                            <Text fontSize="sm" color="gray.400" mb={3}>
                                Esplora only returns ids in this list; fee, size, and inputs/outputs load on the
                                transaction page.
                            </Text>
                            <Stack gap={2}>
                                {transactions.map((tx) => (
                                    <Box
                                        key={tx.txid}
                                        borderWidth="1px"
                                        borderColor="gray.700"
                                        rounded="md"
                                        p={3}
                                        bg="gray.950"
                                        cursor="pointer"
                                        transition="border-color 0.15s ease, background 0.15s ease"
                                        _hover={{ borderColor: 'orange.400', bg: 'gray.900' }}
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
                                        >
                                            {tx.txid}
                                        </Code>
                                    </Box>
                                ))}
                                {transactions.length === 0 && (
                                    <Text color="gray.400">No mempool transactions in this view.</Text>
                                )}
                            </Stack>
                            {hasMore && (
                                <Box mt={4} textAlign="center">
                                    <Button
                                        variant="link"
                                        colorPalette="orange"
                                        onClick={loadMore}
                                        loading={loadingMore}
                                        loadingText="Loading…"
                                    >
                                        Load more ({PAGE_SIZE} more)
                                    </Button>
                                </Box>
                            )}
                        </Box>
                    )}
                </Stack>
            </AppLayout>
        </>
    );
}
