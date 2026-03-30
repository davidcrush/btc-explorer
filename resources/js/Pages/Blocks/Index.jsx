import { Head } from '@inertiajs/react';
import {
    Badge,
    Box,
    Button,
    Code,
    Container,
    Flex,
    Heading,
    HStack,
    Spinner,
    Stack,
    Text,
} from '@chakra-ui/react';
import { useCallback, useEffect, useState } from 'react';
import axios from 'axios';

export default function Index() {
    const [blocks, setBlocks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

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

            <Box minH="100vh" bg="gray.950" color="gray.100" py={10}>
                <Container maxW="6xl">
                    <Stack gap={6}>
                        <Flex justify="space-between" align="center" wrap="wrap" gap={3}>
                            <Box>
                                <Heading size="lg">Bitcoin Latest Blocks</Heading>
                                <Text color="gray.400" mt={1}>
                                    Inertia + React + Chakra UI starter page
                                </Text>
                            </Box>
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
                                    >
                                        <Stack gap={3}>
                                            <HStack>
                                                <Badge colorPalette="orange">Height {block.height}</Badge>
                                                <Badge colorPalette="purple">
                                                    Tx: {block.transactions.length}
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
                                                    Size: {block.size}
                                                </Text>
                                                <Text fontSize="sm" color="gray.300">
                                                    Weight: {block.weight}
                                                </Text>
                                                <Text fontSize="sm" color="gray.300">
                                                    Nonce: {block.nonce}
                                                </Text>
                                                <Text fontSize="sm" color="gray.300">
                                                    Time:{' '}
                                                    {new Date(block.timestamp * 1000).toLocaleString()}
                                                </Text>
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
                </Container>
            </Box>
        </>
    );
}
