import { Head, router } from '@inertiajs/react';
import {
    Badge,
    Box,
    Button,
    Code,
    Flex,
    HStack,
    Skeleton,
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

function BlockCardSkeleton() {
    return (
        <Box borderWidth="1px" borderColor="gray.700" rounded="lg" p={4} bg="gray.900">
            <Stack gap={3}>
                <HStack gap={2}>
                    <Skeleton height="6" width="24" loading />
                    <Skeleton height="6" width="32" loading />
                </HStack>
                <Skeleton height="12" width="full" loading />
                <HStack wrap="wrap" gap={4}>
                    <Skeleton height="4" width="44" loading />
                    <Skeleton height="4" width="36" loading />
                    <Skeleton height="4" width="32" loading />
                    <Skeleton height="4" width="40" loading />
                </HStack>
            </Stack>
        </Box>
    );
}

export default function Index() {
    const [blocks, setBlocks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [loadingMore, setLoadingMore] = useState(false);
    const [hasMore, setHasMore] = useState(false);
    const [error, setError] = useState(null);
    const [relativeTimeTick, setRelativeTimeTick] = useState(0);
    const { formatAmount, blocksPerPage } = useUserPreferences();

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
            const response = await axios.get('/api/v1/btc/blocks', {
                params: { limit: blocksPerPage, offset: 0 },
            });
            const data = response?.data?.data;
            setBlocks(data?.blocks ?? []);
            setHasMore(Boolean(data?.has_more));
        } catch (err) {
            setError(
                err?.response?.data?.message ||
                    'Unable to load blocks right now.'
            );
            setBlocks([]);
            setHasMore(false);
        } finally {
            setLoading(false);
        }
    }, [blocksPerPage]);

    const loadMore = useCallback(async () => {
        if (!hasMore || loadingMore) {
            return;
        }

        setLoadingMore(true);

        try {
            const response = await axios.get('/api/v1/btc/blocks', {
                params: { limit: blocksPerPage, offset: blocks.length },
            });
            const data = response?.data?.data;
            const next = data?.blocks ?? [];
            setBlocks((prev) => [...prev, ...next]);
            setHasMore(Boolean(data?.has_more));
        } catch (err) {
            setError(
                err?.response?.data?.message ||
                    'Unable to load more blocks right now.'
            );
            setHasMore(false);
        } finally {
            setLoadingMore(false);
        }
    }, [blocks.length, blocksPerPage, hasMore, loadingMore]);

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
                        <Stack gap={4}>
                            {Array.from({ length: blocksPerPage }, (_, index) => (
                                <BlockCardSkeleton key={`block-skeleton-${index}`} />
                            ))}
                        </Stack>
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

                            {blocks.length > 0 && hasMore && (
                                <Box textAlign="center" pt={2}>
                                    <Button
                                        variant="ghost"
                                        colorPalette="orange"
                                        onClick={loadMore}
                                        loading={loadingMore}
                                        loadingText="Loading more…"
                                    >
                                        Load more ({blocksPerPage}{' '}
                                        {blocksPerPage === 1 ? 'block' : 'blocks'})
                                    </Button>
                                </Box>
                            )}

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
