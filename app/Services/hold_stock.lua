-- hold_stock.lua
-- Parameters: KEYS[1] = stock_key, ARGV[1] = quantity, ARGV[2] = hold_id, ARGV[3] = ttl_seconds

local current = tonumber(redis.call('GET', KEYS[1]) or '0')
local qty = tonumber(ARGV[1])

if current < qty then
    return -1  -- not enough stock
end

-- Atomically decrease and set expiry + hold tracking
redis.call('DECRBY', KEYS[1], qty)

-- Store hold metadata (optional, for cleanup)
redis.call('HSET', 'holds:'..ARGV[2], 'product_stock_key', KEYS[1], 'quantity', qty, 'created_at', ARGV[3])

-- Expire the hold if payment not completed
redis.call('EXPIRE', KEYS[1], ARGV[3])  -- rough cleanup, fine-tuned later
redis.call('EXPIRE', 'holds:'..ARGV[2], ARGV[3])

return current - qty  -- remaining after hold
