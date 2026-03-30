import { usePage, router } from '@inertiajs/react';
import { Box, Button, Container, Flex, Heading, HStack, Text } from '@chakra-ui/react';
import { useEffect, useMemo, useState } from 'react';
import { useUserPreferences } from '../contexts/UserPreferencesContext';

export default function AppLayout({ title, subtitle, children }) {
    const { url } = usePage();
    const [colorMode, setColorMode] = useState('dark');
    const [showProfile, setShowProfile] = useState(false);
    const { amountUnit, setAmountUnit } = useUserPreferences();

    useEffect(() => {
        const saved = window.localStorage.getItem('color-mode');

        if (saved === 'light' || saved === 'dark') {
            setColorMode(saved);
        }
    }, []);

    useEffect(() => {
        window.localStorage.setItem('color-mode', colorMode);
    }, [colorMode]);

    const isDark = colorMode === 'dark';

    const palette = useMemo(
        () => ({
            bg: isDark ? 'gray.950' : 'gray.50',
            text: isDark ? 'gray.100' : 'gray.900',
            subtext: isDark ? 'gray.400' : 'gray.600',
            panel: isDark ? 'gray.900' : 'white',
            panelBorder: isDark ? 'gray.700' : 'gray.200',
        }),
        [isDark]
    );

    return (
        <Box minH="100vh" bg={palette.bg} color={palette.text}>
            <Box borderBottomWidth="1px" borderColor={palette.panelBorder}>
                <Container maxW="6xl" py={4}>
                    <Flex justify="space-between" align="center" wrap="wrap" gap={3}>
                        <HStack gap={2}>
                            <Button
                                size="sm"
                                variant={url === '/' ? 'solid' : 'ghost'}
                                colorPalette="orange"
                                onClick={() => router.visit('/')}
                            >
                                Home
                            </Button>
                            <Button
                                size="sm"
                                variant={url.startsWith('/blocks') ? 'solid' : 'ghost'}
                                colorPalette="orange"
                                onClick={() => router.visit('/blocks')}
                            >
                                Blocks
                            </Button>
                        </HStack>

                        <HStack>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setColorMode(isDark ? 'light' : 'dark')}
                            >
                                {isDark ? 'Light mode' : 'Dark mode'}
                            </Button>
                            <Box position="relative">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => setShowProfile((open) => !open)}
                                >
                                    Profile
                                </Button>
                                {showProfile && (
                                    <Box
                                        position="absolute"
                                        top="calc(100% + 8px)"
                                        right={0}
                                        minW="220px"
                                        borderWidth="1px"
                                        borderColor={palette.panelBorder}
                                        rounded="md"
                                        bg={palette.panel}
                                        p={3}
                                        zIndex={10}
                                        boxShadow="md"
                                    >
                                        <Text fontSize="sm" fontWeight="medium" mb={2}>
                                            Amount Unit
                                        </Text>
                                        <HStack wrap="wrap">
                                            <Button
                                                size="xs"
                                                variant={amountUnit === 'bitcoin' ? 'solid' : 'outline'}
                                                colorPalette="orange"
                                                onClick={() => setAmountUnit('bitcoin')}
                                            >
                                                btc
                                            </Button>
                                            <Button
                                                size="xs"
                                                variant={amountUnit === 'millibit' ? 'solid' : 'outline'}
                                                colorPalette="orange"
                                                onClick={() => setAmountUnit('millibit')}
                                            >
                                                mBTC
                                            </Button>
                                            <Button
                                                size="xs"
                                                variant={amountUnit === 'bit' ? 'solid' : 'outline'}
                                                colorPalette="orange"
                                                onClick={() => setAmountUnit('bit')}
                                            >
                                                μBTC
                                            </Button>
                                            <Button
                                                size="xs"
                                                variant={amountUnit === 'satoshi' ? 'solid' : 'outline'}
                                                colorPalette="orange"
                                                onClick={() => setAmountUnit('satoshi')}
                                            >
                                                sat
                                            </Button>
                                        </HStack>
                                    </Box>
                                )}
                            </Box>
                        </HStack>
                    </Flex>
                </Container>
            </Box>

            <Container maxW="6xl" py={10}>
                <Heading size="lg">{title}</Heading>
                {subtitle ? (
                    <Text mt={1} mb={6} color={palette.subtext}>
                        {subtitle}
                    </Text>
                ) : null}
                {children}
            </Container>
        </Box>
    );
}
