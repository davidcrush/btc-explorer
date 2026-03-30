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

export default function Show({ hash }) {
    const [block, setBlock] = useState(null);
    const [transactions, setTransactions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [loadingMore, setLoadingMore] = useState(false);
    const [error, setError] = useState(null);

    const fetchBlock = useCallback(async (start = 0, append = false) => {
        if (append) {
            setLoadingMore(true);
        } else {
            setLoading(true);
            setError(null);
        }
        try {
            const response = await axios.get(`/api/v1/btc/blocks/${hash}`, {
                params: {
                    transactions_start: start,
                    transactions_limit: 25,
                },
            });

            const payload = response?.data?.data?.block ?? null;
            setBlock(payload);
            setTransactions((current) =>
                append ? [...current, ...(payload?.transactions ?? [])] : (payload?.transactions ?? [])
            );
        } catch {
            setError('Unable to load block details right now.');
            setBlock(null);
            setTransactions([]);
        } finally {
            if (append) {
                setLoadingMore(false);
            } else {
                setLoading(false);
            }
        }
    }, [hash]);

    useEffect(() => {
        fetchBlock();
    }, [fetchBlock]);

    return (
        <>
            <Head title="Block Details" />

            <AppLayout
                title="Block Details"
                subtitle={hash ? `Hash: ${hash}` : 'Block detail view'}
            >
                <Stack gap={6}>
                    <Flex justify="space-between" align="center" wrap="wrap" gap={3}>
                        <HStack>
                            <Button variant="outline" onClick={() => router.visit('/blocks')}>
                                Back to blocks
                            </Button>
                            <Button colorPalette="orange" onClick={() => fetchBlock(0, false)}>
                                Refresh
                            </Button>
                        </HStack>
                    </Flex>

                    {loading && (
                        <HStack>
                            <Spinner size="sm" color="orange.300" />
                            <Text color="gray.300">Loading block details...</Text>
                        </HStack>
                    )}

                    {!loading && error && (
                        <Box borderWidth="1px" borderColor="red.400" rounded="md" p={4}>
                            <Text color="red.200">{error}</Text>
                        </Box>
                    )}

                    {!loading && !error && block && (
                        <Stack gap={4}>
                            <HStack wrap="wrap">
                                <Badge colorPalette="orange">Height {block.height}</Badge>
                                <Badge colorPalette="blue">
                                    Total tx: {block.total_transactions}
                                </Badge>
                                <Badge colorPalette="purple">Version: {block.version}</Badge>
                            </HStack>

                            <Code whiteSpace="normal" wordBreak="break-all" colorPalette="gray">
                                {block.hash}
                            </Code>

                            <HStack wrap="wrap" gap={4}>
                                <Text fontSize="sm" color="gray.300">
                                    Merkle root: {block.merkle_root}
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Bits: {block.bits}
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Difficulty: {block.difficulty}
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Nonce: {block.nonce}
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Size: {block.size}
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Weight: {block.weight}
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Time: {new Date(block.timestamp * 1000).toLocaleString()}
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Median time: {new Date(block.mediantime * 1000).toLocaleString()}
                                </Text>
                            </HStack>

                            <HStack>
                                <Button
                                    variant="outline"
                                    disabled={!block.previous_block_hash}
                                    onClick={() =>
                                        block.previous_block_hash &&
                                        router.visit(`/blocks/${block.previous_block_hash}`)
                                    }
                                >
                                    Previous block
                                </Button>
                                <Button
                                    variant="outline"
                                    disabled={!block.next_block_hash}
                                    onClick={() =>
                                        block.next_block_hash &&
                                        router.visit(`/blocks/${block.next_block_hash}`)
                                    }
                                >
                                    Next block
                                </Button>
                            </HStack>

                            <Box borderWidth="1px" borderColor="gray.700" rounded="lg" p={4} bg="gray.900">
                                <Text fontWeight="medium" mb={3}>
                                    Transactions ({transactions.length} of {block.total_transactions})
                                </Text>
                                <Stack gap={2}>
                                    {transactions.map((txid) => (
                                        <Code
                                            key={txid}
                                            whiteSpace="normal"
                                            wordBreak="break-all"
                                            colorPalette="gray"
                                        >
                                            {txid}
                                        </Code>
                                    ))}
                                    {transactions.length === 0 && (
                                        <Text color="gray.400">No transactions available.</Text>
                                    )}
                                </Stack>
                                {block.has_more_transactions && (
                                    <Box mt={4}>
                                        <Button
                                            variant="link"
                                            colorPalette="orange"
                                            disabled={loadingMore || block.next_transactions_start === null}
                                            onClick={() =>
                                                block.next_transactions_start !== null &&
                                                fetchBlock(block.next_transactions_start, true)
                                            }
                                        >
                                            {loadingMore ? 'Loading...' : 'Load more'}
                                        </Button>
                                    </Box>
                                )}
                            </Box>
                        </Stack>
                    )}
                </Stack>
            </AppLayout>
        </>
    );
}
