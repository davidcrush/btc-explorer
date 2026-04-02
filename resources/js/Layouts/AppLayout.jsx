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
import { useState } from 'react';
import {
    BLOCKS_PER_PAGE_OPTIONS,
    useUserPreferences,
} from '../contexts/UserPreferencesContext';

const GITHUB_REPO_URL = 'https://github.com/davidcrush/btc-explorer';

const palette = {
    bg: 'gray.950',
    text: 'gray.100',
    subtext: 'gray.400',
    panel: 'gray.900',
    panelBorder: 'gray.700',
    headerOutline: {
        borderColor: 'gray.500',
        color: 'gray.50',
        bg: 'whiteAlpha.200',
        _hover: { bg: 'whiteAlpha.300' },
        _expanded: { bg: 'whiteAlpha.300' },
    },
};

export default function AppLayout({ title, subtitle, children }) {
    const { url } = usePage();
    const [showProfile, setShowProfile] = useState(false);
    const { amountUnit, setAmountUnit, blocksPerPage, setBlocksPerPage } =
        useUserPreferences();

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
                                _hover={{ bg: 'whiteAlpha.100' }}
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
                                <Button
                                    size="sm"
                                    variant={url.startsWith('/transactions') ? 'solid' : 'ghost'}
                                    colorPalette="orange"
                                    onClick={() => router.visit('/transactions')}
                                >
                                    Transactions
                                </Button>
                                <Button
                                    size="sm"
                                    variant={url.startsWith('/mempool') ? 'solid' : 'ghost'}
                                    colorPalette="orange"
                                    onClick={() => router.visit('/mempool')}
                                >
                                    Mempool
                                </Button>
                            </HStack>
                        </Flex>

                        <HStack>
                            <IconButton
                                as="a"
                                href={GITHUB_REPO_URL}
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="View source on GitHub"
                                size="sm"
                                variant="outline"
                                rounded="full"
                                {...palette.headerOutline}
                            >
                                <Icon
                                    asChild={false}
                                    viewBox="0 0 24 24"
                                    boxSize="1.15em"
                                    fill="currentColor"
                                >
                                    <path
                                        fillRule="evenodd"
                                        clipRule="evenodd"
                                        d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.532 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                    />
                                </Icon>
                            </IconButton>
                            <Box position="relative">
                                <IconButton
                                    aria-label="Preferences"
                                    aria-expanded={showProfile}
                                    size="sm"
                                    variant="outline"
                                    rounded="full"
                                    {...palette.headerOutline}
                                    onClick={() => setShowProfile((open) => !open)}
                                >
                                    <Icon
                                        asChild={false}
                                        viewBox="0 0 24 24"
                                        boxSize="1.15em"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth={1.5}
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <path d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.213-1.28Z" />
                                        <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </Icon>
                                </IconButton>
                                {showProfile && (
                                    <Box
                                        position="absolute"
                                        top="calc(100% + 8px)"
                                        right={0}
                                        minW="240px"
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
                                                ₿
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
                                                Satoshi
                                            </Button>
                                        </HStack>

                                        <Text fontSize="sm" fontWeight="medium" mt={4} mb={2}>
                                            Blocks per page
                                        </Text>
                                        <HStack wrap="wrap">
                                            {BLOCKS_PER_PAGE_OPTIONS.map((n) => (
                                                <Button
                                                    key={n}
                                                    size="xs"
                                                    variant={
                                                        blocksPerPage === n ? 'solid' : 'outline'
                                                    }
                                                    colorPalette="orange"
                                                    onClick={() => setBlocksPerPage(n)}
                                                >
                                                    {n}
                                                </Button>
                                            ))}
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
