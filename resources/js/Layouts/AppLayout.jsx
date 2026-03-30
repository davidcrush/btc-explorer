import { usePage, router } from '@inertiajs/react';
import {
    Box,
    Button,
    Container,
    Flex,
    Heading,
    HStack,
    Icon,
    IconButton,
    Text,
} from '@chakra-ui/react';
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
                        <Flex align="center" gap={{ base: 4, md: 8 }} flexWrap="wrap">
                            <Button
                                variant="ghost"
                                size="sm"
                                h="auto"
                                py={1}
                                px={2}
                                onClick={() => router.visit('/')}
                                _hover={{ bg: isDark ? 'whiteAlpha.100' : 'blackAlpha.50' }}
                            >
                                <Text
                                    as="span"
                                    fontFamily="mono"
                                    fontSize="md"
                                    fontWeight="semibold"
                                    letterSpacing="tight"
                                    color={palette.text}
                                >
                                    <Text as="span" color="orange.400">
                                        btc
                                    </Text>
                                    <Text as="span" color={palette.subtext}>
                                        -explorer
                                    </Text>
                                </Text>
                            </Button>

                            <HStack gap={1} pl={{ base: 0, sm: 2 }} borderLeftWidth={{ base: 0, sm: '1px' }} borderColor={palette.panelBorder}>
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
                        </Flex>

                        <HStack>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setColorMode(isDark ? 'light' : 'dark')}
                            >
                                {isDark ? 'Light mode' : 'Dark mode'}
                            </Button>
                            <Box position="relative">
                                <IconButton
                                    aria-label="Profile and preferences"
                                    aria-expanded={showProfile}
                                    size="sm"
                                    variant="outline"
                                    rounded="full"
                                    onClick={() => setShowProfile((open) => !open)}
                                >
                                    <Icon
                                        asChild={false}
                                        viewBox="0 0 24 24"
                                        boxSize="1.15em"
                                    >
                                        <path
                                            fill="currentColor"
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"
                                        />
                                    </Icon>
                                </IconButton>
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
