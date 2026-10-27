from alembic import op
import sqlalchemy as sa

revision = "2025_08_28_0001_init"
down_revision = None
branch_labels = None
depends_on = None

def upgrade():
    op.create_table(
        "bot_users",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("telegram_id", sa.BigInteger, nullable=False, unique=True),
        sa.Column("username", sa.String(length=64), nullable=True),
        sa.Column("language_code", sa.String(length=10), nullable=True),
        sa.Column("is_bot", sa.Boolean, nullable=False, server_default=sa.false()),
        sa.Column("is_active", sa.Boolean, nullable=False, server_default=sa.true()),
        sa.Column("created_at", sa.DateTime, nullable=False),
        sa.Column("updated_at", sa.DateTime, nullable=False),
    )
    op.create_table(
        "chats",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("chat_id", sa.BigInteger, nullable=False, unique=True),
        sa.Column("type", sa.String(length=32), nullable=False),
        sa.Column("title", sa.String(length=255), nullable=True),
        sa.Column("created_at", sa.DateTime, nullable=False),
    )
    op.create_table(
        "messages",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("update_id", sa.BigInteger, index=True),
        sa.Column("chat_id", sa.BigInteger, index=True),
        sa.Column("from_user_id", sa.BigInteger, nullable=True),
        sa.Column("text", sa.String(length=4096), nullable=True),
        sa.Column("raw", sa.JSON, nullable=True),
        sa.Column("created_at", sa.DateTime, nullable=False),
    )
    op.create_table(
        "webhook_events",
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("update_id", sa.BigInteger, index=True),
        sa.Column("headers", sa.JSON, nullable=True),
        sa.Column("payload", sa.JSON, nullable=True),
        sa.Column("received_at", sa.DateTime, nullable=False),
    )

def downgrade():
    op.drop_table("webhook_events")
    op.drop_table("messages")
    op.drop_table("chats")
    op.drop_table("bot_users")
