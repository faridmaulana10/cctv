import asyncio
import websockets

async def handler(websocket):
    while True:
        try:
            msg = await websocket.recv()
            for client in clients:
                await client.send(msg)
        except:
            break

async def main():
    async with websockets.serve(handler, "0.0.0.0", 8000):
        print("WebSocket Server running on ws://localhost:8000")
        await asyncio.Future()

clients = set()
asyncio.run(main())
