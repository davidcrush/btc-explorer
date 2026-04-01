import { Head, router } from '@inertiajs/react';
import { Button, Code, HStack, Stack, Text } from '@chakra-ui/react';
import AppLayout from '../Layouts/AppLayout';

function Endpoint({ method, path, query, children }) {
    const line = query ? `${path}?${query}` : path;

    return (
        <Stack gap={1}>
            <Text fontSize="sm" color="gray.300">
                {children}
            </Text>
            <Code p={3} rounded="md" whiteSpace="normal" wordBreak="break-all" display="block">
                {method} {line}
            </Code>
        </Stack>
    );
}

export default function Home() {
    return (
        <>
            <Head title="Home" />

            <AppLayout
                title="BTC Explorer"
                subtitle="Laravel + Inertia + React — Blockstream Esplora APIs, cached with Redis"
            >
                <Stack gap={8}>
                    <Text>
                        Public JSON endpoints live under{' '}
                        <Code as="span" px={1.5} py={0.5} rounded="sm">
                            /api/v1
                        </Code>
                        . The Blocks and Transactions pages call these from the browser.
                    </Text>

                    <Stack gap={4}>
                        <Text fontWeight="semibold" fontSize="lg">
                            Blocks
                        </Text>

                        <Endpoint
                            method="GET"
                            path="/api/v1/btc/blocks"
                            query="limit=10&offset=0"
                        >
                            List recent blocks. Query:{' '}
                            <Text as="span" fontFamily="mono" fontSize="sm">
                                limit
                            </Text>{' '}
                            (1–100, default 10),{' '}
                            <Text as="span" fontFamily="mono" fontSize="sm">
                                offset
                            </Text>{' '}
                            (0–2000, default 0). Response includes{' '}
                            <Text as="span" fontFamily="mono" fontSize="sm">
                                data.blocks
                            </Text>{' '}
                            and{' '}
                            <Text as="span" fontFamily="mono" fontSize="sm">
                                data.has_more
                            </Text>
                            .
                        </Endpoint>

                        <Endpoint
                            method="GET"
                            path="/api/v1/btc/blocks/{hash}"
                            query="transactions_start=0&transactions_limit=25"
                        >
                            Block detail plus a page of transactions. Query:{' '}
                            <Text as="span" fontFamily="mono" fontSize="sm">
                                transactions_start
                            </Text>{' '}
                            (default 0),{' '}
                            <Text as="span" fontFamily="mono" fontSize="sm">
                                transactions_limit
                            </Text>{' '}
                            (1–25, default 25).
                        </Endpoint>
                    </Stack>

                    <Stack gap={4}>
                        <Text fontWeight="semibold" fontSize="lg">
                            Transactions
                        </Text>

                        <Endpoint method="GET" path="/api/v1/btc/transactions/recent">
                            Up to ten confirmed transaction summaries (txid + fee) from recent
                            blocks, for the transactions index page.
                        </Endpoint>

                        <Endpoint method="GET" path="/api/v1/btc/transactions/{txid}">
                            Full transaction: inputs, outputs, fee, size, vsize, block link when
                            confirmed.{' '}
                            <Text as="span" fontFamily="mono" fontSize="sm">
                                txid
                            </Text>{' '}
                            must be 64 hex characters.
                        </Endpoint>
                    </Stack>

                    <HStack flexWrap="wrap" gap={3}>
                        <Button colorPalette="orange" onClick={() => router.visit('/blocks')}>
                            View latest blocks
                        </Button>
                        <Button colorPalette="orange" variant="outline" onClick={() => router.visit('/transactions')}>
                            View transactions
                        </Button>
                    </HStack>
                </Stack>
            </AppLayout>
        </>
    );
}
