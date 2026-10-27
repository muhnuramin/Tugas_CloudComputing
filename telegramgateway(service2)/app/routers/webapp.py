from fastapi import APIRouter, Query

router = APIRouter(prefix="/webapp", tags=["webapp"])

@router.get("/auth")
async def webapp_auth(init_data: str = Query(..., description="Telegram WebApp init data")):
    return {"ok": True}
