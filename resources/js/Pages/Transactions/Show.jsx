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

export default function Show({ txid }) {
    const [transaction, setTransaction] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const { formatAmount } = useUserPreferences();

    const fetchTransaction = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get(`/api/v1/btc/transactions/${txid}`);
            setTransaction(response?.data?.data?.transaction ?? null);
        } catch {
            setError('Unable to load transaction details right now.');
            setTransaction(null);
        } finally {
            setLoading(false);
        }
    }, [txid]);

    useEffect(() => {
        fetchTransaction();
    }, [fetchTransaction]);

    const title = transaction?.txid
        ? `Transaction ${transaction.txid.slice(0, 12)}…`
        : 'Transaction Details';

    return (
        <>
            <Head title="Transaction Details" />

            <AppLayout title={title} subtitle={txid ? `Txid: ${txid}` : 'Transaction detail view'}>
                <Stack gap={6}>
                    <Flex justify="space-between" align="center" wrap="wrap" gap={3}>
                        <HStack>
                            <Button variant="outline" onClick={() => router.visit('/transactions')}>
                                All transactions
                            </Button>
                            <Button variant="outline" onClick={() => router.visit('/blocks')}>
                                Back to blocks
                            </Button>
                            <Button colorPalette="orange" onClick={() => fetchTransaction()}>
                                Refresh
                            </Button>
                        </HStack>
                    </Flex>

                    {loading && (
                        <HStack>
                            <Spinner size="sm" color="orange.300" />
                            <Text color="gray.300">Loading transaction...</Text>
                        </HStack>
                    )}

                    {!loading && error && (
                        <Box borderWidth="1px" borderColor="red.400" rounded="md" p={4}>
                            <Text color="red.200">{error}</Text>
                        </Box>
                    )}

                    {!loading && !error && transaction && (
                        <Stack gap={4}>
                            <HStack wrap="wrap">
                                {transaction.confirmed ? (
                                    <Badge colorPalette="green">Confirmed</Badge>
                                ) : (
                                    <Badge colorPalette="yellow">Unconfirmed</Badge>
                                )}
                                {transaction.is_coinbase && (
                                    <Badge colorPalette="orange">Coinbase</Badge>
                                )}
                                {transaction.block_height != null && (
                                    <Badge colorPalette="blue">Height {transaction.block_height}</Badge>
                                )}
                            </HStack>

                            <Code whiteSpace="normal" wordBreak="break-all" colorPalette="gray">
                                {transaction.txid}
                            </Code>

                            <Stack gap={2}>
                                {transaction.block_hash ? (
                                    <HStack align="flex-start" wrap="wrap" gap={2}>
                                        <Text fontSize="sm" color="gray.300" flexShrink={0}>
                                            Block:
                                        </Text>
                                        <Button
                                            variant="link"
                                            colorPalette="orange"
                                            whiteSpace="normal"
                                            wordBreak="break-all"
                                            justifyContent="flex-start"
                                            h="auto"
                                            py={0}
                                            minW={0}
                                            textAlign="left"
                                            onClick={() =>
                                                router.visit(`/blocks/${transaction.block_hash}`)
                                            }
                                        >
                                            {transaction.block_hash}
                                        </Button>
                                    </HStack>
                                ) : (
                                    <Text fontSize="sm" color="gray.400">
                                        Block: not yet mined (mempool)
                                    </Text>
                                )}
                                <Text fontSize="sm" color="gray.300">
                                    Fee: {formatAmount(transaction.fee)}
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Size: {transaction.size} bytes
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Virtual size (vsize): {transaction.virtual_size} vB
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Weight: {transaction.weight} WU
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Inputs total: {formatAmount(transaction.input_total)}
                                </Text>
                                <Text fontSize="sm" color="gray.300">
                                    Outputs total: {formatAmount(transaction.output_total)}
                                </Text>
                            </Stack>

                            <Box borderWidth="1px" borderColor="gray.700" rounded="lg" p={4} bg="gray.900">
                                <Text fontWeight="medium" mb={3}>
                                    Inputs
                                </Text>
                                <Stack gap={1} mb={4}>
                                    {transaction.inputs.map((input, index) => (
                                        <Text
                                            key={`${transaction.txid}-in-${index}`}
                                            fontSize="xs"
                                            color="gray.400"
                                        >
                                            {input.is_coinbase
                                                ? 'Coinbase input'
                                                : `${input.address ?? 'Unknown address'} — ${formatAmount(input.value)}`}
                                        </Text>
                                    ))}
                                    {transaction.inputs.length === 0 && (
                                        <Text fontSize="xs" color="gray.500">
                                            No inputs
                                        </Text>
                                    )}
                                </Stack>
                                <Text fontWeight="medium" mb={3}>
                                    Outputs
                                </Text>
                                <Stack gap={1}>
                                    {transaction.outputs.map((output, index) => (
                                        <Text
                                            key={`${transaction.txid}-out-${index}`}
                                            fontSize="xs"
                                            color="gray.400"
                                        >
                                            {(output.address ?? 'Unknown address')} —{' '}
                                            {formatAmount(output.value)}
                                        </Text>
                                    ))}
                                    {transaction.outputs.length === 0 && (
                                        <Text fontSize="xs" color="gray.500">
                                            No outputs
                                        </Text>
                                    )}
                                </Stack>
                            </Box>
                        </Stack>
                    )}
                </Stack>
            </AppLayout>
        </>
    );
}
