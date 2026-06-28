import { useState, useEffect } from 'react';

export default function useCart(tableCode) {
    const cartKey = tableCode ? `gokasir_cart_${tableCode}` : 'gokasir_cart';

    // Initialize state from localStorage
    const [cart, setCart] = useState(() => {
        try {
            const item = window.localStorage.getItem(cartKey);
            return item ? JSON.parse(item) : [];
        } catch (error) {
            console.error("Error reading localStorage", error);
            return [];
        }
    });

    // Save to localStorage whenever cart changes
    useEffect(() => {
        try {
            window.localStorage.setItem(cartKey, JSON.stringify(cart));
            
            // Dispatch a custom event so other components on the same page can react
            window.dispatchEvent(new Event('cartUpdated'));
        } catch (error) {
            console.error("Error setting localStorage", error);
        }
    }, [cart, cartKey]);

    const addToCart = (product) => {
        setCart(prevCart => {
            const existing = prevCart.find(item => item.product_id === product.id);
            if (existing) {
                return prevCart.map(item =>
                    item.product_id === product.id
                        ? { ...item, qty: item.qty + 1 }
                        : item
                );
            }
            return [...prevCart, {
                product_id: product.id,
                name: product.name,
                price: product.selling_price,
                qty: 1,
                image: product.photo_url || null
            }];
        });
    };

    const updateQty = (productId, newQty) => {
        setCart(prevCart => {
            if (newQty <= 0) {
                return prevCart.filter(item => item.product_id !== productId);
            }
            return prevCart.map(item =>
                item.product_id === productId
                    ? { ...item, qty: newQty }
                    : item
            );
        });
    };

    const removeFromCart = (productId) => {
        setCart(prevCart => prevCart.filter(item => item.product_id !== productId));
    };

    const updateNote = (productId, note) => {
        setCart(prevCart => prevCart.map(item => 
            item.product_id === productId 
                ? { ...item, note: note } 
                : item
        ));
    };

    const clearCart = () => {
        setCart([]);
    };

    const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

    return {
        cart,
        addToCart,
        updateQty,
        updateNote,
        removeFromCart,
        clearCart,
        totalQty,
        subtotal
    };
}
