import { Head, router } from '@inertiajs/react';
import { Box, Button, Code, Stack, Text } from '@chakra-ui/react';
import AppLayout from '../Layouts/AppLayout';

export default function Home() {
    return (
        <>
            <Head title="Home" />

            <AppLayout
                title="BTC Explorer"
                subtitle="Laravel + Inertia + React + Chakra UI starter"
            >
                <Stack gap={4}>
                    <Text>
                        Latest blocks are available at the API endpoint below and rendered in the
                        Blocks page.
                    </Text>

                    <Code p={3} rounded="md" whiteSpace="normal" wordBreak="break-all">
                        GET /api/v1/btc/blocks?limit=10
                    </Code>

                    <Box>
                        <Button colorPalette="orange" onClick={() => router.visit('/blocks')}>
                            View latest blocks
                        </Button>
                    </Box>
                </Stack>
            </AppLayout>
        </>
    );
}
