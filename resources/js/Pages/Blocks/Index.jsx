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
    TooltipContent,
    TooltipPositioner,
    TooltipRoot,
    TooltipTrigger,
} from '@chakra-ui/react';
import { useCallback, useEffect, useState } from 'react';
import axios from 'axios';
import AppLayout from '../../Layouts/AppLayout';
import { useUserPreferences } from '../../contexts/UserPreferencesContext';
import { formatBlockFullDateTime, formatBlockRelativeTime } from '../../utils/blockTimestamp';

export default function Index() {
    const [blocks, setBlocks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [relativeTimeTick, setRelativeTimeTick] = useState(0);
    const { formatAmount } = useUserPreferences();

    useEffect(() => {
        const id = window.setInterval(() => {
            setRelativeTimeTick((n) => n + 1);
        }, 60_000);

        return () => window.clearInterval(id);
    }, []);

    const fetchBlocks = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await axios.get('/api/v1/btc/blocks?limit=10');
            setBlocks(response?.data?.data?.blocks ?? []);
        } catch {
            setError('Unable to load blocks right now.');
            setBlocks([]);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchBlocks();
    }, [fetchBlocks]);

    return (
        <>
            <Head title="Latest Blocks" />

            <AppLayout
                title="Bitcoin Latest Blocks"
                subtitle="Fetched from Blockstream and cached via Redis"
            >
                <Stack gap={6}>
                    <Flex justify="space-between" align="center" wrap="wrap" gap={3}>
                        <Button onClick={fetchBlocks} colorPalette="orange" variant="solid">
                            Refresh
                        </Button>
                    </Flex>

                    {loading && (
                        <HStack>
                            <Spinner size="sm" color="orange.300" />
                            <Text color="gray.300">Loading blocks...</Text>
                        </HStack>
                    )}

                    {!loading && error && (
                        <Box borderWidth="1px" borderColor="red.400" rounded="md" p={4}>
                            <Text color="red.200">{error}</Text>
                        </Box>
                    )}

                    {!loading && !error && (
                        <Stack gap={4}>
                            {blocks.map((block) => (
                                <Box
                                    key={block.hash}
                                    borderWidth="1px"
                                    borderColor="gray.700"
                                    rounded="lg"
                                    p={4}
                                    bg="gray.900"
                                    cursor="pointer"
                                    onClick={() => router.visit(`/blocks/${block.hash}`)}
                                >
                                    <Stack gap={3}>
                                        <HStack>
                                            <Badge colorPalette="orange">Height {block.height}</Badge>
                                            <Badge colorPalette="blue">
                                                Total tx: {block.total_transactions}
                                            </Badge>
                                        </HStack>

                                        <Code
                                            whiteSpace="normal"
                                            wordBreak="break-all"
                                            colorPalette="gray"
                                        >
                                            {block.hash}
                                        </Code>

                                        <HStack wrap="wrap" gap={4}>
                                            <Text fontSize="sm" color="gray.300">
                                                Miner: {block.miner ?? 'Unknown'}
                                            </Text>
                                            <Text fontSize="sm" color="gray.300">
                                                Reward: {formatAmount(block.block_reward)}
                                            </Text>
                                            <Text fontSize="sm" color="gray.300">
                                                Fees: {formatAmount(block.total_fees)}
                                            </Text>
                                            <TooltipRoot openDelay={250} closeDelay={100}>
                                                <TooltipTrigger asChild>
                                                    <Text
                                                        as="span"
                                                        fontSize="sm"
                                                        color="gray.300"
                                                        cursor="help"
                                                        borderBottomWidth="1px"
                                                        borderBottomStyle="dotted"
                                                        borderBottomColor="gray.500"
                                                        display="inline"
                                                    >
                                                        Time:{' '}
                                                        {formatBlockRelativeTime(
                                                            block.timestamp,
                                                            relativeTimeTick
                                                        )}
                                                    </Text>
                                                </TooltipTrigger>
                                                <TooltipPositioner>
                                                    <TooltipContent
                                                        px={3}
                                                        py={2}
                                                        maxW="sm"
                                                        textStyle="sm"
                                                        textAlign="center"
                                                    >
                                                        {formatBlockFullDateTime(block.timestamp)}
                                                    </TooltipContent>
                                                </TooltipPositioner>
                                            </TooltipRoot>
                                        </HStack>
                                    </Stack>
                                </Box>
                            ))}

                            {blocks.length === 0 && (
                                <Text color="gray.400">No blocks available.</Text>
                            )}
                        </Stack>
                    )}
                </Stack>
            </AppLayout>
        </>
    );
}
